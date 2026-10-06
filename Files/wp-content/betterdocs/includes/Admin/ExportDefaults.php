<?php
namespace WPDeveloper\BetterDocs\Admin;


// Doc/category-listing primitives (meta_query/tax_query, post__not_in/exclude)
// are intrinsic to BetterDocs' KB / category / FAQ filters and intentional.
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
class ExportDefaults {
    public static function get_default_args(): array {
        return [
            'content'    => 'all',
            'author'     => false,
            'category'   => false,
            'start_date' => false,
            'end_date'   => false,
            'status'     => 'publish',
            'offset'     => 0,
            'limit'      => -1,
            'meta_query' => [],
            'query_args' => [],
        ];
    }
}
