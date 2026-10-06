<?php

namespace WPDeveloper\BetterDocsPro\REST;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class PopularKeywords extends BaseAPI {
    public function register() {
        $this->get( '/popular_search_keyword', [$this, 'popular_search_keyword'] );
    }

    /**
     * Deliberately public — BaseAPI::permission_check() now fails closed, and
     * this route must stay open.
     *
     * The front-end search modal calls it for logged-out visitors
     * (assets/build/public/js/extend-search-modal.js), and the payload is the
     * popular-search term list the modal already renders on the page.
     *
     * @return bool
     */
    public function permission_check() {
        return true;
    }

    public function popular_search_keyword( $object ) {
        return betterdocs_pro()->query->popular_search_keyword();
    }
}
