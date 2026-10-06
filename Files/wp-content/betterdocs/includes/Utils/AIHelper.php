<?php

namespace WPDeveloper\BetterDocs\Utils;

use WPDeveloper\BetterDocs\Core\Settings;
use WPDeveloper\BetterDocs\AI\ProviderFactory;

class AIHelper {

    /**
     * Settings instance
     *
     * @var Settings
     */
    private $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Build a provider factory bound to the current settings.
     *
     * @return ProviderFactory
     */
    private function factory() {
        return new ProviderFactory( $this->settings );
    }

    /**
     * Get the API key for the active AI platform.
     *
     * @return string
     */
    public function get_api_key() {
        $factory = $this->factory();
        return $factory->api_key_for( $factory->active_platform() );
    }

    /**
     * Check if OpenAI API key is configured
     *
     * @return bool
     */
    public function has_api_key() {
        $api_key = $this->get_api_key();
        return ! empty( $api_key );
    }

    /**
     * Validate OpenAI API key
     *
     * @param string $api_key Optional API key to validate, uses stored key if not provided
     * @return array Array with 'valid' boolean and 'message' string
     */
    public function validate_api_key( $api_key = '' ) {
        if ( empty( $api_key ) ) {
            $api_key = $this->get_api_key();
        }

        if ( empty( $api_key ) ) {
            return array(
                'valid'   => false,
                'message' => 'Please Insert your <a href="/admin.php?page=betterdocs-settings#betterdocs-ai">API Key</a> to use AI features.'
            );
        }

        $factory = $this->factory();
        return $factory->validate( $factory->active_platform(), $api_key );
    }

    /**
     * Minimum token policy by (feature context, model family). Used as the
     * single source of truth for:
     *  - server-side save validation (Core/Settings.php)
     *  - field UI props sent to the React notice (Core/Settings.php)
     *  - runtime payload floor in the provider layer (AI\Providers\BaseProvider::floor_tokens)
     *
     * Override the whole map (or any cell) via the `betterdocs_ai_min_tokens`
     * filter. Returns 0 when no minimum applies (unknown context or model).
     *
     * @param string $context Feature key, e.g. 'write_with_ai' or 'article_summary'.
     * @param string $model   OpenAI model identifier.
     * @return int Minimum recommended max_tokens for that pair.
     */
    public static function get_min_tokens( $context, $model ) {
        $family = self::token_family( $model );
        $map = apply_filters( 'betterdocs_ai_min_tokens', array(
            'write_with_ai'   => array( 'gpt-4' => 2500, 'gpt-5' => 4500, 'gpt-5.5' => 10000 ),
            'article_summary' => array( 'gpt-4' => 1500, 'gpt-5' => 2500, 'gpt-5.5' => 10000 ),
        ) );
        return isset( $map[ $context ][ $family ] ) ? (int) $map[ $context ][ $family ] : 0;
    }

    /**
     * Map a model id to its token-floor family key.
     *
     * gpt-5.x point releases (gpt-5.5, gpt-5.1, ...) generate much larger, slower
     * responses and need a heavier floor than the base gpt-5 family, so they get
     * their own 'gpt-5.5' key. Plain gpt-5* stays 'gpt-5'; everything else 'gpt-4'.
     *
     * @param string $model OpenAI model identifier.
     * @return string Family key used in the min-token map.
     */
    private static function token_family( $model ) {
        if ( self::is_gpt5_point_release( $model ) ) {
            return 'gpt-5.5';
        }
        return ( 0 === strpos( (string) $model, 'gpt-5' ) ) ? 'gpt-5' : 'gpt-4';
    }

    /**
     * Whether a model is a gpt-5.x point release (gpt-5.5, gpt-5.1, ...), which
     * use the newer reasoning_effort vocabulary and need a heavier token floor.
     *
     * @param string $model OpenAI model identifier.
     * @return bool
     */
    public static function is_gpt5_point_release( $model ) {
        return (bool) preg_match( '/^gpt-5\.\d/', (string) $model );
    }

    /**
     * Return the threshold map for one context, in {family => min} shape, so
     * Settings.php can serialize it onto a field for the React notice to read.
     *
     * @param string $context Feature key.
     * @return array<string,int>
     */
    public static function get_min_tokens_map( $context ) {
        return array(
            'gpt-4'   => self::get_min_tokens( $context, 'gpt-4o' ),
            'gpt-5'   => self::get_min_tokens( $context, 'gpt-5' ),
            'gpt-5.5' => self::get_min_tokens( $context, 'gpt-5.5' ),
        );
    }

    /**
     * Make a chat-completion request to the active AI platform.
     *
     * Provider-agnostic: the platform, model, key, payload shape and parsing are
     * resolved by ProviderFactory. The model is the global `ai_model`; callers
     * may still override per request via $options['model'].
     *
     * @param array $messages Array of messages for the chat completion.
     * @param array $options  Optional parameters (model, max_tokens, temperature, timeout).
     * @return string|\WP_Error API response content or error.
     */
    public function make_openai_request( $messages, $options = array() ) {
        $defaults = array(
            'max_tokens'  => (int) $this->settings->get( 'article_summary_max_token', 1500 ),
            'temperature' => 0.7,
            'timeout'     => 50,
            'context'     => 'article_summary',
        );

        $options = wp_parse_args( $options, $defaults );

        $result = $this->factory()->make()->chat( $messages, $options );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $result['content'];
    }

    /**
     * Analyze article quality using OpenAI
     *
     * @param string $content Article content to analyze
     * @param string $title Article title (optional)
     * @return array|\WP_Error Analysis result with score and feedback
     */
    public function analyze_article_quality( $content, $title = '' ) {
        if ( empty( $content ) ) {
            return new \WP_Error( 'empty_content', 'Article content cannot be empty.' );
        }

        // Create analysis prompt
        $prompt = $this->build_quality_analysis_prompt( $content, $title );

        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are an expert content analyst specializing in documentation quality assessment. Provide detailed, actionable feedback to help improve article quality.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );

        $options = array(
            'max_tokens' => 2000,
            'temperature' => 0.3 // Lower temperature for more consistent analysis
        );

        $response = $this->make_openai_request( $messages, $options );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Parse the AI response into structured data
        return $this->parse_quality_analysis_response( $response );
    }

    /**
     * Build the prompt for article quality analysis
     *
     * @param string $content Article content
     * @param string $title Article title
     * @return string Formatted prompt
     */
    private function build_quality_analysis_prompt( $content, $title = '' ) {
        $title_section = ! empty( $title ) ? "Title: {$title}\n\n" : '';

        $prompt = "Please analyze the following documentation article for quality and provide a comprehensive assessment:\n\n";
        $prompt .= $title_section;
        $prompt .= "Content:\n{$content}\n\n";
        $prompt .= "Please evaluate the article based on these criteria and provide your response in the following JSON format:\n\n";
        $prompt .= "{\n";
        $prompt .= '  "overall_score": 91,';
        $prompt .= '  "scores": {';
        $prompt .= '    "clarity": 90,';
        $prompt .= '    "completeness": 92,';
        $prompt .= '    "relevance": 95,';
        $prompt .= '    "structure": 87,';
        $prompt .= '    "readability": 88';
        $prompt .= '  },';
        $prompt .= '  "feedback": {';
        $prompt .= '    "strengths": ["Clear headings", "Good use of examples"],';
        $prompt .= '    "improvements": ["Add more detailed explanations in section 2", "Include troubleshooting steps"],';
        $prompt .= '    "suggestions": ["Consider adding screenshots", "Break down complex paragraphs"]';
        $prompt .= '  },';
        $prompt .= '  "priority": "medium"';
        $prompt .= "}\n\n";
        $prompt .= "Scoring criteria (0-100):\n";
        $prompt .= "- Clarity: How clear and understandable is the content?\n";
        $prompt .= "- Completeness: Does it cover the topic thoroughly?\n";
        $prompt .= "- Relevance: Is the content relevant to the stated purpose?\n";
        $prompt .= "- Structure: Is the content well-organized with proper headings?\n";
        $prompt .= "- Readability: Is it easy to read and follow?\n\n";
        $prompt .= "Priority levels: low (80+), medium (60-79), high (below 60)\n";
        $prompt .= "Provide specific, actionable feedback that content creators can implement.";

        return $prompt;
    }

    /**
     * Parse AI response into structured quality analysis data
     *
     * @param string $response Raw AI response
     * @return array|\WP_Error Parsed analysis data
     */
    private function parse_quality_analysis_response( $response ) {
        // Try to extract JSON from the response
        $json_start = strpos( $response, '{' );
        $json_end   = strrpos( $response, '}' );

        if ( false === $json_start || false === $json_end ) {
            return new \WP_Error( 'parse_error', 'Could not find valid JSON in AI response.' );
        }

        $json_string = substr( $response, $json_start, $json_end - $json_start + 1 );
        $data        = json_decode( $json_string, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error( 'json_error', 'Invalid JSON in AI response: ' . json_last_error_msg() );
        }

        // Validate required fields
        $required_fields = array( 'overall_score', 'scores', 'feedback' );
        foreach ( $required_fields as $field ) {
            if ( ! isset( $data[ $field ] ) ) {
                return new \WP_Error( 'missing_field', "Missing required field: {$field}" );
            }
        }

        // Ensure scores are within valid range
        $data[ 'overall_score' ] = max( 0, min( 100, intval( $data[ 'overall_score' ] ) ) );

        if ( isset( $data[ 'scores' ] ) && is_array( $data[ 'scores' ] ) ) {
            foreach ( $data[ 'scores' ] as $key => $score ) {
                $data[ 'scores' ][ $key ] = max( 0, min( 100, intval( $score ) ) );
            }
        }

        // Set default priority if not provided
        if ( ! isset( $data[ 'priority' ] ) ) {
            $overall_score = $data[ 'overall_score' ];
            if ( $overall_score >= 80 ) {
                $data[ 'priority' ] = 'low';
            } elseif ( $overall_score >= 60 ) {
                $data[ 'priority' ] = 'medium';
            } else {
                $data[ 'priority' ] = 'high';
            }
        }

        return $data;
    }

    /**
     * Save article quality score as post meta
     *
     * @param int $post_id Post ID
     * @param array $quality_data Quality analysis data
     * @return bool Success status
     */
    public function save_article_quality_score( $post_id, $quality_data ) {
        if ( empty( $post_id ) || ! is_array( $quality_data ) ) {
            return false;
        }

        // Save the complete analysis data
        $saved = update_post_meta( $post_id, '_betterdocs_article_quality_analysis', $quality_data );

        // Save just the overall score for easy querying
        update_post_meta( $post_id, '_betterdocs_article_quality_score', $quality_data[ 'overall_score' ] );

        // Save analysis timestamp
        update_post_meta( $post_id, '_betterdocs_article_quality_analyzed_at', current_time( 'mysql' ) );

        return false !== $saved;
    }

    /**
     * Get article quality score from post meta
     *
     * @param int $post_id Post ID
     * @return array|false Quality analysis data or false if not found
     */
    public function get_article_quality_score( $post_id ) {
        if ( empty( $post_id ) ) {
            return false;
        }

        $quality_data = get_post_meta( $post_id, '_betterdocs_article_quality_analysis', true );

        if ( empty( $quality_data ) ) {
            return false;
        }

        // Add timestamp if available
        $analyzed_at = get_post_meta( $post_id, '_betterdocs_article_quality_analyzed_at', true );
        if ( $analyzed_at ) {
            $quality_data[ 'analyzed_at' ] = $analyzed_at;
        }

        return $quality_data;
    }

    /**
     * Check if article needs re-analysis based on last modified date
     *
     * @param int $post_id Post ID
     * @return bool True if re-analysis is needed
     */
    public function needs_reanalysis( $post_id ) {
        $analyzed_at = get_post_meta( $post_id, '_betterdocs_article_quality_analyzed_at', true );

        if ( empty( $analyzed_at ) ) {
            return true; // Never analyzed
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        // Check if post was modified after last analysis
        $post_modified      = strtotime( $post->post_modified );
        $analyzed_timestamp = strtotime( $analyzed_at );

        return $post_modified > $analyzed_timestamp;
    }

    /**
     * Create a system message for OpenAI
     *
     * @param string $content System message content
     * @return array Message array
     */
    public function create_system_message( $content ) {
        return array(
            'role' => 'system',
            'content' => $content
        );
    }

    /**
     * Create a user message for OpenAI
     *
     * @param string $content User message content
     * @return array Message array
     */
    public function create_user_message( $content ) {
        return array(
            'role' => 'user',
            'content' => $content
        );
    }

    /**
     * Create messages array for article summarization
     *
     * @param string $title Article title
     * @param string $content Article content
     * @return array Messages array
     */
    public function create_summary_messages( $title, $content ) {
        $system_message = $this->create_system_message(
            'You are a helpful assistant that creates concise, informative summaries of documentation articles. Always format your response in clean HTML with paragraph tags. Do not use markdown formatting, code blocks, or backticks. Return only the HTML content without any wrapper formatting.'
        );

        $user_prompt = "Please provide a concise summary of the following article titled '{$title}'. The summary should be 2-3 paragraphs long, highlighting the main points and key takeaways. Format the response in HTML with proper paragraph tags. Do not wrap the response in markdown code blocks or use any markdown formatting.\n\nArticle content:\n{$content}";

        $user_message = $this->create_user_message( $user_prompt );

        return array( $system_message, $user_message );
    }

    /**
     * Create messages array for content generation
     *
     * @param string $prompt User prompt
     * @param string $keywords Optional keywords
     * @return array Messages array
     */
    public function create_content_messages( $prompt, $keywords = '' ) {
        $system_message = $this->create_system_message(
            'You are a helpful assistant who writes documentation for users.'
        );

        $user_message = $this->create_user_message( $prompt );

        return array( $system_message, $user_message );
    }

    /**
     * Sanitize and prepare content for AI processing
     *
     * @param string $content Raw content
     * @param int $max_length Maximum length to keep
     * @return string Sanitized content
     */
    public function prepare_content_for_ai( $content, $max_length = 4000 ) {
        // Strip HTML tags and decode entities
        $content = wp_strip_all_tags( $content );
        $content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

        // Remove extra whitespace
        $content = preg_replace( '/\s+/', ' ', $content );
        $content = trim( $content );

        // Limit length
        if ( strlen( $content ) > $max_length ) {
            $content = substr( $content, 0, $max_length );
            // Try to cut at a word boundary
            $last_space = strrpos( $content, ' ' );
            if ( false !== $last_space && $last_space > $max_length * 0.8 ) {
                $content = substr( $content, 0, $last_space );
            }
            $content .= '...';
        }

        return $content;
    }

    /**
     * Check if AI features are enabled
     *
     * @return bool
     */
    public function is_ai_enabled() {
        return $this->settings->get( 'enable_write_with_ai', true ) && $this->has_api_key();
    }

    /**
     * Get AI usage statistics (placeholder for future implementation)
     *
     * @return array Usage statistics
     */
    public function get_usage_stats() {
        // This could be implemented to track API usage, costs, etc.
        return array(
            'requests_today' => 0,
            'tokens_used' => 0,
            'cost_estimate' => 0
        );
    }
}
