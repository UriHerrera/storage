<?php

namespace WPDeveloper\BetterDocs\Utils;

use WP_Error;

class Database {
	/**
	 * Cache DB Data
	 * @var array
	 */
	private $cache = [];

	/**
	 * Retrieves theme modification value for the active theme.
	 *
	 * @since 2.5.0
	 *
	 * @param string $key
	 * @param mixed   $default
	 *
	 * @return mixed
	 */
	public function get_theme_mod( $key, $default = false ) {
		return get_theme_mod( $key, $default );
	}

	public function get( $key, $default = false ) {
		if ( empty( $key ) ) {
			return new WP_Error( 'invalid_key', __( 'Key cannot be empty.', 'betterdocs' ), [ 'status' => 404 ] );
		}

		if ( ! isset( $this->cache[ $key ] ) ) {
			$this->cache[ $key ] = get_option( $key, $default );
		}

		// update the cache when new values are updated(for wpml specifically, other usecase might be available)
		if ( $this->cache[ $key ] != get_option( $key, $default ) ) {
			$this->cache[ $key ] = get_option( $key, $default );
		}

		return $this->cache[ $key ];
	}

	/**
	 * Summary of save
	 * @param mixed $key
	 * @param mixed $value
	 * @return bool
	 */
	public function save( $key, $value ) {
		$_updated = update_option( $key, $value, 'no' );

		if ( $_updated ) {
			$this->cache[ $key ] = $value;
		}

		return $_updated;
	}

	/**
	 * Summary of get_cache
	 * @param string|int $key
	 * @param bool $force
	 * @param string $group
	 * @return bool|mixed
	 */
	public function get_cache( $key, $force = false, $group = 'betterdocs' ) {
		return wp_cache_get( $key, $group, $force );
	}

	/**
	 * Set cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 * @param string $group
	 * @return bool
	 */
	public function set_cache( $key, $value, $expire = 2, $group = 'betterdocs' ) {
		$expire = $expire * DAY_IN_SECONDS;
		return wp_cache_set( $key, $value, $group, $expire );
	}

	public function flush_cache( $group = 'betterdocs' ) {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( $group );
		} else {
			// @todo need to do by cache_key
		}
	}

	public function set_transient( $transient, $value, $expiration = null ) {
		$expiration = $expiration == null ? MINUTE_IN_SECONDS : $expiration;
		return set_transient( $transient, $value, $expiration );
	}

	public function get_transient( $transient ) {
		return get_transient( $transient );
	}

	public function delete_transient( $transient ) {
		return delete_transient( $transient );
	}

	/**
	 * Versioned-key incrementor. Reading the current version lets callers build
	 * cache keys like "{namespace}_v{N}_..."; bumping the version makes every
	 * key in that namespace unreachable in one update.
	 *
	 * The canonical value lives in wp_options so invalidation persists across
	 * requests even on sites with no external object cache (where wp_cache_*
	 * is per-request in-memory only). wp_cache_* is still used as a
	 * within-request memoization layer.
	 */
	public function get_cache_version( $namespace, $group = 'betterdocs' ) {
		$cache_key  = $namespace . '_version';
		$option_key = 'bd_cache_v_' . $namespace;
		$v = wp_cache_get( $cache_key, $group );
		if ( false === $v ) {
			$v = (int) get_option( $option_key, 1 );
			if ( $v < 1 ) {
				$v = 1;
			}
			wp_cache_set( $cache_key, $v, $group, 0 );
		}
		return (int) $v;
	}

	public function bump_cache_version( $namespace, $group = 'betterdocs' ) {
		$cache_key  = $namespace . '_version';
		$option_key = 'bd_cache_v_' . $namespace;
		$new = (int) get_option( $option_key, 1 ) + 1;
		update_option( $option_key, $new, false );
		wp_cache_set( $cache_key, $new, $group, 0 );
	}
}
