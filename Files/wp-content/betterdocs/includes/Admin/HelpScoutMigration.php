<?php
namespace WPDeveloper\BetterDocs\Admin;

use WPDeveloper\BetterDocs\Admin\Importer\WPImport;
use WPDeveloper\BetterDocs\Admin\BackgroundProcess\WP_Background_Process;

class HelpScoutMigration extends WP_Background_Process {
	/**
	 * @var string
	 */
	protected $action = 'betterdocs_helpscout_migration';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		if ( empty( $item ) ) {
			return false;
		}

		$headers          = $item['headers'];
		$initial_response = $item['initial_response'];
		$api_key          = $item['api_key'];
		$collection_id    = $item['collection_id'];
		// Implement your migration logic here
		$this->migrateFromHelpScout( $headers, $initial_response, $api_key, $collection_id );

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Perform any cleanup tasks after all items are processed
	}

	/**
	 * Migrate articles from Help Scout to WordPress
	 *
	 * @param string $api_key Help Scout API key
	 * @param int $collection_id Help Scout collection ID
	 * @return bool True if migration is successful, false otherwise
	 */
	protected function migrateFromHelpScout( $headers, $initial_response, $api_key, $collection_id ) {
		$total_pages = get_option( 'betterdocs_helpscout_total_pages' );

		if ( ! $total_pages ) {
			if ( is_wp_error( $initial_response ) ) {
				return false;
			}
			$initial_body = wp_remote_retrieve_body( $initial_response );
			$initial_data = json_decode( $initial_body, true );
			$total_pages  = isset( $initial_data['articles']['pages'] ) ? $initial_data['articles']['pages'] : 0;
			update_option( 'betterdocs_helpscout_total_pages', $total_pages );
		}

		$current_page  = get_option( 'betterdocs_helpscout_current_page', 1 );
		$allArticleIds = [];

		try {
			// Fetch articles page by page
			while ( $current_page <= $total_pages ) {
				$api_endpoint = 'https://docsapi.helpscout.net/v1/collections/' . $collection_id . '/articles?pageSize=1&page=' . $current_page;

				$response = wp_remote_get( $api_endpoint, [ 'headers' => $headers ] );

				if ( is_wp_error( $response ) ) {
					return false;
				}

				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );

				if ( ! isset( $data['articles'] ) || ! isset( $data['articles']['items'] ) ) {
					return false;
				}

				$articles = $data['articles']['items'];

				// Fetch detailed information for each article
				$detailedArticles = [];
				foreach ( $articles as $article ) {
					$articleId     = $article['id'];
					$articleNumber = $article['number'];

					if ( ! $this->articleIdExists( $articleId ) ) {
						$articleEndpoint = "https://docsapi.helpscout.net/v1/articles/{$articleId}";
						$articleResponse = wp_remote_get( $articleEndpoint, [ 'headers' => $headers ] );

						if ( ! is_wp_error( $articleResponse ) ) {
							$articleBody = wp_remote_retrieve_body( $articleResponse );
							$articleData = json_decode( $articleBody, true );

							if ( isset( $articleData['article'] ) && isset( $articleData['article']['text'] ) ) {
								$article['text']  = $articleData['article']['text'];
								$categories       = $articleData['article']['categories'];
								$categoryDetails  = [];
								$categories_cache = get_transient( 'betterdocs_helpscout_categories' );

								// Initialize $categories_cache as an array if it's not set
								if ( ! is_array( $categories_cache ) ) {
									$categories_cache = [];
								}

								foreach ( $categories as $categoryId ) {
									if ( isset( $categories_cache[ $categoryId ] ) ) {
										$categoryDetails[] = $categories_cache[ $categoryId ];
									} else {
										$categoryInfo = $this->fetchCategoryInfo( $categoryId, $headers );
										if ( $categoryInfo ) {
											$categoryDetails[]               = $categoryInfo;
											$categories_cache[ $categoryId ] = $categoryInfo;
											set_transient( 'betterdocs_helpscout_categories', $categories_cache, DAY_IN_SECONDS ); // Cache for a day
										}
									}
								}

								$article['categories'] = $categoryDetails;
								$detailedArticles[]    = $article;
								$allArticleIds[]       = $articleId;
							}
						}
					}
				}

				if ( ! empty( $detailedArticles ) ) {
					$wp_importer = new WPImport( '' );
					$wp_importer->import_helpscout_data( $detailedArticles );
					$existingArticleIds = get_option( 'betterdocs_helpscout_migrated_article_ids', [] );
					$existingArticleIds = is_array( $existingArticleIds ) ? $existingArticleIds : [];
					$allArticleIds      = array_merge( $existingArticleIds, $allArticleIds );
					update_option( 'betterdocs_helpscout_migrated_article_ids', serialize( $allArticleIds ) );
				}

				++$current_page;

				update_option( 'betterdocs_helpscout_current_page', $current_page );

				// Check if we have processed all pages
				if ( $current_page > $total_pages ) {
					update_option( 'betterdocs_helpscout_current_page', 1 );
					delete_option( 'betterdocs_helpscout_total_pages' );
				}
			}
		} catch ( \WP_Error $wp_error ) {
			return false;
		} catch ( \Exception $e ) {
			return false;
		}

		return true; // Indicate success
	}

	/**
	 * Fetch category information from Help Scout API.
	 *
	 * @param string $categoryId Category ID
	 * @param array $headers Headers for API requests
	 * @return array|null Category information
	 */
	protected function fetchCategoryInfo( $categoryId, $headers ) {
		$categoryEndpoint = "https://docsapi.helpscout.net/v1/categories/{$categoryId}";
		$categoryResponse = wp_remote_get( $categoryEndpoint, [ 'headers' => $headers ] );

		if ( ! is_wp_error( $categoryResponse ) ) {
			$categoryBody = wp_remote_retrieve_body( $categoryResponse );
			$categoryData = json_decode( $categoryBody, true );

			if ( isset( $categoryData['category']['name'] ) && isset( $categoryData['category']['slug'] ) ) {
				return [
					'name' => $categoryData['category']['name'],
					'slug' => $categoryData['category']['slug']
				];
			}
		}
		return null;
	}

	/**
	 * Check if article ID exists in the option.
	 *
	 * @param int $articleId Article ID
	 * @return bool True if article ID exists, false otherwise
	 */
	protected function articleIdExists( $articleId ) {
		$existingArticleIds = get_option( 'betterdocs_helpscout_migrated_article_ids' );
		$existingArticleIds = $existingArticleIds ? unserialize( $existingArticleIds ) : [];
		return in_array( $articleId, $existingArticleIds );
	}
}
