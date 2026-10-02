<?php
/**
 * Typed errors returned by BetterDocs abilities.
 *
 * @package BetterDocs
 * @since   4.9.0
 */

namespace WPDeveloper\BetterDocs\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Factories for the typed errors an ability may return.
 *
 * Every factory produces a `\WP_Error` whose **code** is the error slug and
 * whose **data** repeats that slug under `error`, alongside the fields that make
 * the failure actionable. `MCPServer` serialises that data object as the
 * `tools/call` error payload, so an AI client sees a machine-readable reason
 * rather than a sentence it has to parse.
 *
 * Two conventions hold across every factory:
 *
 * - `status` is the HTTP status the same failure would carry over REST.
 * - `fixable_by_agent` is true **only** when the fix is a tool call the agent can
 *   make on its own — enabling a setting, or resending with a corrected field.
 *   Anything needing a human (a capability grant, installing Pro) is false.
 *
 * @since 4.9.0
 */
class AbilityError {

	/**
	 * The caller lacks the capability the ability is gated on.
	 *
	 * @since 4.9.0
	 *
	 * @param string $capability Capability that was missing.
	 * @param string $what       What the caller was trying to do, as a phrase ("create a doc category").
	 * @return \WP_Error
	 */
	public static function capability_missing( $capability, $what ) {
		return self::make(
			'capability_missing',
			sprintf(
				/* translators: 1: capability name, 2: what the caller was trying to do. */
				__( 'You need the "%1$s" capability to %2$s.', 'betterdocs' ),
				$capability,
				$what
			),
			[
				'capability'       => $capability,
				'fixable_by_agent' => false,
				'fix_hint'         => __( 'Ask an administrator to grant it (bd-get-status lists missing capabilities).', 'betterdocs' ),
				'status'           => 403
			]
		);
	}

	/**
	 * The feature lives in BetterDocs Pro, which is not usable on this site.
	 *
	 * Never returned for an unlicensed-but-active Pro: that state is reported,
	 * not refused (ADR-004).
	 *
	 * @since 4.9.0
	 *
	 * @param array  $pro_state Pro state array; its `state` key is echoed back.
	 * @param string $what      Feature name, as a phrase ("Knowledge bases").
	 * @return \WP_Error
	 */
	public static function pro_required( array $pro_state, $what ) {
		$state = isset( $pro_state['state'] ) ? (string) $pro_state['state'] : 'pro_not_installed';

		switch ( $state ) {
			case 'pro_not_active':
				/* translators: %s: feature name. */
				$message = sprintf( __( '%s need BetterDocs Pro, which is installed but not active on this site.', 'betterdocs' ), $what );
				break;
			case 'pro_unlicensed':
				/* translators: %s: feature name. */
				$message = sprintf( __( '%s need BetterDocs Pro, which is active but has no valid licence on this site.', 'betterdocs' ), $what );
				break;
			default:
				/* translators: %s: feature name. */
				$message = sprintf( __( '%s need BetterDocs Pro, which is not installed on this site.', 'betterdocs' ), $what );
				break;
		}

		return self::make(
			'pro_required',
			$message,
			[
				'state'            => $state,
				'requires_pro'     => true,
				'fixable_by_agent' => false,
				'status'           => 403
			]
		);
	}

	/**
	 * The feature is available but a setting switches it off.
	 *
	 * The only error the agent can clear by itself: `fix` is a literal
	 * `bd-update-settings` call.
	 *
	 * @since 4.9.0
	 *
	 * @param string $setting  Setting key that is off.
	 * @param array  $fix_args Settings map that would turn it on, e.g. `[ 'multiple_kb' => true ]`.
	 * @param string $what     Feature name, as a phrase ("Multiple Knowledge Base").
	 * @param string $state    Pro state slug this refusal belongs to. Default 'pro_active_setting_off'.
	 * @return \WP_Error
	 */
	public static function setting_disabled( $setting, array $fix_args, $what, $state = 'pro_active_setting_off' ) {
		return self::make(
			'setting_disabled',
			sprintf(
				/* translators: %s: feature name. */
				__( '%s is off. Enable it and this will work (from the next request).', 'betterdocs' ),
				$what
			),
			[
				'state'            => $state,
				'setting'          => $setting,
				'requires_pro'     => 0 === strpos( $state, 'pro_' ),
				'fixable_by_agent' => true,
				'fix'              => [
					'tool' => 'bd-update-settings',
					'args' => [ 'settings' => $fix_args ]
				],
				'status'           => 409
			]
		);
	}

	/**
	 * An input field is missing, malformed, or outside the allowed set.
	 *
	 * @since 4.9.0
	 *
	 * @param string     $field   Field name the caller got wrong.
	 * @param string     $message Why it is wrong, and what would be right.
	 * @param array|null $allowed Optional. The allowed values, when there is a closed set.
	 * @return \WP_Error
	 */
	public static function invalid_input( $field, $message, $allowed = null ) {
		$data = [
			'field'            => $field,
			'fixable_by_agent' => true,
			'status'           => 400
		];

		if ( null !== $allowed ) {
			$data['allowed'] = array_values( (array) $allowed );
		}

		return self::make( 'invalid_input', $message, $data );
	}

	/**
	 * The addressed object does not exist.
	 *
	 * @since 4.9.0
	 *
	 * @param string     $object_type Object type ("doc", "term", "faq").
	 * @param int|string $id          Identifier that was looked up.
	 * @return \WP_Error
	 */
	public static function not_found( $object_type, $id ) {
		return self::make(
			'not_found',
			sprintf(
				/* translators: 1: object type, 2: identifier. */
				__( 'No %1$s found with the id %2$s.', 'betterdocs' ),
				$object_type,
				$id
			),
			[
				'object'           => $object_type,
				'id'               => $id,
				'fixable_by_agent' => false,
				'status'           => 404
			]
		);
	}

	/**
	 * The request collides with something that already exists, or with the
	 * object's current state.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message What collided.
	 * @param array  $extra   Extra typed fields to merge into the data object.
	 * @return \WP_Error
	 */
	public static function conflict( $message, array $extra = [] ) {
		return self::make(
			'conflict',
			$message,
			array_merge(
				[
					'fixable_by_agent' => false,
					'status'           => 409
				],
				$extra
			)
		);
	}

	/**
	 * Something BetterDocs called failed — a REST controller, a service class,
	 * or a `\Throwable` caught in `AbilityBase::execute_wrapper()`.
	 *
	 * The message is the real reason, not a generic one: without it a failed
	 * write is indistinguishable from a refused one at the client.
	 *
	 * @since 4.9.0
	 *
	 * @param string $message Underlying failure message.
	 * @param array  $extra   Extra typed fields to merge into the data object.
	 * @return \WP_Error
	 */
	public static function upstream( $message, array $extra = [] ) {
		return self::make(
			'upstream_error',
			$message,
			array_merge(
				[
					'fixable_by_agent' => false,
					'status'           => 500
				],
				$extra
			)
		);
	}

	/**
	 * A write tool was called with a read-only credential.
	 *
	 * @since 4.9.0
	 *
	 * @param string $tool MCP tool name that was refused.
	 * @return \WP_Error
	 */
	public static function read_only( $tool ) {
		return self::make(
			'read_only_credential',
			sprintf(
				/* translators: %s: MCP tool name. */
				__( 'The credential this connection uses is read-only, so "%s" cannot run.', 'betterdocs' ),
				$tool
			),
			[
				'tool'             => $tool,
				'fixable_by_agent' => false,
				'status'           => 403
			]
		);
	}

	/**
	 * The MCP client asked for a tool this site does not have.
	 *
	 * @since 4.9.0
	 *
	 * @param string $name Tool name that was asked for.
	 * @return \WP_Error
	 */
	public static function unknown_tool( $name ) {
		return self::make(
			'unknown_tool',
			sprintf(
				/* translators: %s: MCP tool name. */
				__( 'There is no tool called "%s" on this site. Call tools/list for the current catalog.', 'betterdocs' ),
				$name
			),
			[
				'tool'             => $name,
				'fixable_by_agent' => false,
				'status'           => 404
			]
		);
	}

	/**
	 * Build the `\WP_Error`, keeping the code and `data['error']` in step.
	 *
	 * @since 4.9.0
	 *
	 * @param string $code    Error slug.
	 * @param string $message Human-readable reason.
	 * @param array  $data    Typed fields.
	 * @return \WP_Error
	 */
	private static function make( $code, $message, array $data ) {
		return new \WP_Error(
			$code,
			$message,
			array_merge(
				[
					'error'   => $code,
					'message' => $message
				],
				$data
			)
		);
	}
}
