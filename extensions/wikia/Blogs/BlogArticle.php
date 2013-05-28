<?php
/**
 * blog listing for user, something similar to CategoryPage
 *
 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
 * @author Adrtian Wieczorek <adi@wkia-inc.com>
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This is MediaWiki extension.\n";
	exit( 1 ) ;
}

class BlogArticle extends Article {

	public $mProps;

	/**
	 * how many entries on listing
	 */
	private $mCount = 5;

	/**
	 * setup function called on initialization
	 * Create a Category:BlogListingPage so that we can purge by category when new blogs are posted
	 * moved other setup code to ::ArticleFromTitle instead of hooking that twice [owen]
	 *
	 * @access public
	 * @static
	 */
	public static function createCategory() {
		// make sure page "Category:BlogListingTag" exists
		$title = Title::newFromText( 'Category:BlogListingPage' );
		if ( !$title->exists() && $this->getContext()->getUser->isAllowed( 'edit' ) ) {
			$article = new Article( $title );
			$article->doEdit(
				"__HIDDENCAT__", $title, EDIT_NEW | EDIT_FORCE_BOT | EDIT_SUPPRESS_RC
			);
		}
	}

	/**
	 * overwritten Article::view function
	 */
	public function view() {
		global $wgOut, $wgRequest, $wgTitle;

		$feed = $this->getContext()->getRequest->getText( "feed", false );
		if( $feed && in_array( $feed, array( "rss", "atom" ) ) ) {
			$this->showFeed( $feed );
		}
		elseif ( $this->getTitle()->isSubpage() ) {
			/**
			 * blog article, show if exists
			 */
			$oldPrefixedText = $this->mTitle->mPrefixedText;
			list( $author, $prefixedText )  = explode('/', $this->mTitle->getPrefixedText(), 2);
			if( isset( $prefixedText ) && !empty( $prefixedText ) ) {
				$this->mTitle->mPrefixedText = $prefixedText;
			}
			$this->mTitle->mPrefixedText = $oldPrefixedText;
			$this->mProps = self::getProps( $this->mTitle->getArticleID() );
			Article::view();
		}
		else {
			/**
			 * blog listing
			 */
			$this->getContext()->getOutput()->setHTMLTitle( $this->getContext()->getOutput()->getWikiaPageTitle( $this->mTitle->getPrefixedText() ) );
			$this->showBlogListing();
		}
	}

	/**
	 * take data from blog tag extension and display it
	 *
	 * @access private
	 */
	private function showBlogListing() {
		global $wgMemc;
		$request = $this->getContext()->getRequest();
		$output = $this->getContext()->getOutput();

		/**
		 * use cache or skip cache when action=purge
		 */
		$user    = $this->mTitle->getBaseText();
		$userMem = $this->mTitle->getPrefixedDBkey();
		$listing = false;
		$purge   = $request->getVal( "action" ) == 'purge';
		$page    = $request->getVal( "page", 0 );
		$offset  = $page * $this->mCount;

		$output->setSyndicated( true );

		if( !$purge ) {
			$listing  = $wgMemc->get( wfMemcKey( "blog", "listing", $userMem, $page ) );
		}

		if( !$listing ) {
			$text = "
				<bloglist
					count=$this->mCount
					summary=true
					summarylength=750
					type=plain
					title=Blogs
					offset=$offset>
					<author>$user</author>
					</bloglist>";
			$parser = new Parser;
			$parserOutput = $parser->parse($text, $this->mTitle,  new ParserOptions());
			$listing = $parserOutput->getText();
			$wgMemc->set( wfMemcKey( "blog", "listing", $userMem, $page ), $listing, 3600 );
		}

		$output->addHTML( $listing );
	}


	/**
	 * clear data from memcache and purge any pages in Category:BlogListingPage
	 *
	 * @access public
	 */
	public function clearBlogListing() {
		global $wgMemc;

		// Clear Oasis rail module
		$mcKey = wfMemcKey( "OasisPopularBlogPosts", $this->getContext()->getLanguage()->getCode() );
		$wgMemc->delete($mcKey);

		$user = $this->mTitle->getPrefixedDBkey();
		foreach( range(0, 5) as $page ) {
			$wgMemc->delete( wfMemcKey( "blog", "listing", $user, $page ) );
		}
		$this->doPurge();

		$title = Title::newFromText( 'Category:BlogListingPage' );
		$title->touchLinks();

	}

	/**
	 * generate xml feed from returned data
	 */
	private function showFeed( $format ) {
		global $wgMemc, $wgFeedClasses, $wgSitename;

		$user    = $this->mTitle->getBaseText();
		$userMemc = $this->mTitle->getPrefixedDBkey();
		$listing = false;
		$purge   = $this->getContext()->getRequest()->getVal( 'action' ) == 'purge';
		$offset  = 0;

		wfProfileIn( __METHOD__ );

		if( !$purge ) {
			$listing  = $wgMemc->get( wfMemcKey( "blog", "feed", $userMemc, $offset ) );
		}

		if ( !$listing ) {
			$params = array(
				"count"  => 50,
				"summary" => true,
				"summarylength" => 750,
				"type" => "array",
				"title" => "Blogs",
				"offset" => $offset
			);

			$listing = BlogTemplateClass::parseTag( "<author>$user</author>", $params, new Parser );
			$wgMemc->set( wfMemcKey( "blog", "feed", $userMemc, $offset ), $listing, 3600 );
		}

		$feed = new $wgFeedClasses[ $format ]( wfMessage( "blog-userblog", $user )->text(), wfMessage( "blog-fromsitename", $wgSitename )->text(), $this->getTitle()->getFullUrl() );

		$feed->outHeader();
		if( is_array( $listing ) ) {
			foreach( $listing as $item ) {
				$title = Title::newFromText( $item["title"], NS_BLOG_ARTICLE );
				$item = new FeedItem(
					$title->getSubpageText(),
					$item["description"],
					$item["url"],
					$item["timestamp"],
					$item["author"]
				);
				$feed->outItem( $item );
			}
		}
		$feed->outFooter();

		wfProfileOut( __METHOD__ );
	}

	/**
	 * private function
	 *
	 * @access private
	 */
	private function __makefeedLink( $type, $mime ) {
		return Xml::element( 'link', array(
			'rel' => 'alternate',
			'type' => $mime,
			'href' => $this->mTitle->getLocalUrl( "feed={$type}" ) )
		);
	}

	/**
	 * static entry point for hook
	 *
	 * @static
	 * @access public
	 */
	static public function ArticleFromTitle( &$Title, &$Article ) {
		// macbre: check namespace (RT #16832)
		if ( !in_array($Title->getNamespace(), array(NS_BLOG_ARTICLE, NS_BLOG_ARTICLE_TALK, NS_BLOG_LISTING, NS_BLOG_LISTING_TALK)) ) {
			return true;
		}

		if( $Title->getNamespace() == NS_BLOG_ARTICLE ) {
			$Article = new BlogArticle( $Title );
		}

		return true;
	}


	/**
	 * return list of props
	 *
	 * @access public
	 * @static
	 *
	 */

	static public function getPropsList() {
		$replace = array('voting' => WPP_BLOGS_VOTING, 'commenting' => WPP_BLOGS_COMMENTING );
		return $replace;
	}

	/**
	 * save article extra properties to page_props table
	 *
	 * @access public
	 * @static
	 *
	 * @param array $props array of properties to save (prop name => prop value)
	 */
	static public function setProps( $page_id, Array $props ) {
		wfProfileIn( __METHOD__ );
		$dbw = wfGetDB( DB_MASTER );

		$replace = self::getPropsList();
		foreach( $props as $sPropName => $sPropValue) {
			wfSetWikiaPageProp($replace[$sPropName], $page_id, $sPropValue );
		}

		$dbw->commit(); #--- for ajax
		wfProfileOut( __METHOD__ );
	}

	/**
	 * get properties for page, maybe it should be cached?
	 *
	 * @access public
	 * @static
	 *
	 * @return Array
	 */
	static public function getProps( $page_id ) {
		wfProfileIn( __METHOD__ );

		$return = array();
		$types = self::getPropsList();
		foreach( $types as $key => $value ) {
			$return[$key] =  (int) wfGetWikiaPageProp( $value, $page_id );
		}

		wfProfileOut( __METHOD__ );
		wfDebug( __METHOD__ . ": getting props for $page_id\n" );

		return $return;
	}

	/**
	 * static methods used in Hooks
	 */
	static public function getOtherSection( &$catView, &$output ) {
		global $wgContLang;
		wfProfileIn(__METHOD__);

		/* @var $catView CategoryViewer */
		if( !isset( $catView->blogs ) ) {
			wfProfileOut(__METHOD__);
			return true;
		}
		$ti = htmlspecialchars( $catView->title->getText() );
		$r = '';
		$cat = $catView->getCat();

		$dbcnt = self::blogsInCategory($cat);
		$rescnt = count( $catView->blogs );
		$countmsg = self::getCountMessage( $catView, $rescnt, $dbcnt, 'article' );

		// order blog entries alphabetically
		ksort($catView->blogs);

		$catView->blogs_start_char = array();
		foreach($catView->blogs as $key => $entry) {
			$catView->blogs_start_char[] = $wgContLang->convert( $wgContLang->firstChar($key) );
		}

		if( $rescnt > 0 ) {
			$r = "<div id=\"mw-pages\">\n";
			$r .= '<h2>' . wfMessage( "blog-header", $ti )->text() . "</h2>\n";
			$r .= $countmsg;
			$r .= $catView->getSectionPagingLinksExt( 'page' );
			$r .= $catView->formatList( array_values($catView->blogs), $catView->blogs_start_char );
			$r .= $catView->getSectionPagingLinksExt( 'page' );
			$r .= "\n</div>";
		}
		$output = $r;

		wfProfileOut(__METHOD__);
		return true;
	}

	static public function blogsInCategory ( $cat ) {
		global $wgMemc;
		$titleText = $cat->getTitle()->getDBkey();
		$memKey = self::getCountKey( $titleText );

		$count = $wgMemc->get( $memKey );

		if (empty($count)) {
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				array('page', 'categorylinks'),
				'count(*) as count',
				array(
					'page_id = cl_from',
					'page_namespace' => array(NS_BLOG_ARTICLE, NS_BLOG_LISTING),
					'cl_to' => $titleText,
				),
				__METHOD__
			);

			$count = 0;
			if ( $res->numRows() > 0 ) {
				while ( $row = $res->fetchObject() ) {
					$count = $row->count;
				}
				$dbr->freeResult( $res );
			}

			$wgMemc->set($memKey, $count);
		}

		return $count;
	}

	/**
	 * Hook - AfterCategoriesUpdate
	 */
	static public function clearCountCache ($categoryInserts, $categoryDeletes, $title) {
		global $wgMemc;

		// Clear the count cache for inserts
		foreach ($categoryInserts as $catName => $prefix) {
			$memKey = self::getCountKey( $catName );
			$wgMemc->delete($memKey);
		}

		// Clear the count cache for deletes
		foreach ($categoryDeletes as $catName => $prefix) {
			$memKey = self::getCountKey( $catName );
			$wgMemc->delete($memKey);
		}

		return true;
	}

	static public function getCountKey ($catName) {
		return wfMemcKey( 'blog', 'category', 'count', $catName );
	}

	/*
	 * static method to get number of pages in category
	 */
	static public function getCountMessage( &$catView, $rescnt, $dbcnt, $type ) {
		$lang = $this->getContext()->getLanguage();
		# See CategoryPage->getCountMessage() function
		$totalrescnt = count( $catView->blogs ) + count( $catView->children ) + ($catView->showGallery ? $catView->gallery->count() : 0);
		if ($dbcnt == $rescnt || (($totalrescnt == $catView->limit || $catView->from || $catView->until) && $dbcnt > $rescnt)) {
			# Case 1: seems sane.
			$totalcnt = $dbcnt;
		} elseif ( $totalrescnt < $catView->limit && !$catView->from && !$catView->until ) {
			# Case 2: not sane, but salvageable.
			$totalcnt = $rescnt;
		} else {
			# Case 3: hopeless.  Don't give a total count at all.
			return wfMessage( "blog-subheader", $lang->formatNum( $rescnt ) )->parse();
		}
		return wfMessage( "blog-subheader-all", $lang->formatNum( $rescnt ), $lang->formatNum( $totalcnt ) )->parse();
	}

	/**
	 * Hook
	 */
	static public function addCategoryPage( &$catView, &$title, &$row ) {
		global $wgContLang;

		if( in_array( $row->page_namespace, array( NS_BLOG_ARTICLE, NS_BLOG_LISTING ) ) ) {
			/**
			 * initialize CategoryView->blogs array
			 */
			if( !isset( $catView->blogs ) ) {
				$catView->blogs = array();
			}

			/**
			 * initialize CategoryView->blogs_start_char array
			 */
			if( !isset( $catView->blogs_start_char ) ) {
				$catView->blogs_start_char = array();
			}

			// remove user blog:foo from displayed titles (requested by Angie)
			// "User blog:Homersimpson89/Best Simpsons episode..." -> "Best Simpsons episode..."
			$text = $title->getSubpageText();
			$userName = $title->getBaseText();
			$link = $catView->getSkin()->link($title, $userName." - ".$text);

			// blogs entries will be sorted using this key
			$index = $wgContLang->uc("{$userName}-{$text}");

			$catView->blogs[$index] = $row->page_is_redirect
				? '<span class="redirect-in-category">' . $link . '</span>'
				: $link;

			/**
			 * when we return false it won't be displayed as normal category but
			 * in "other" categories
			 */
			return false;
		}
		return true;
	}

	/**
	 * hook, add link to toolbar
	 */
	static public function skinTemplateTabs( $skin, &$tabs ) {
		global $wgEnableSemanticMediaWikiExt, $wgEnableBlogCommentEdit;
		$title = $this->getTitle();

		if ( ! in_array( $title->getNamespace(), array( NS_BLOG_ARTICLE, NS_BLOG_LISTING, NS_BLOG_ARTICLE_TALK ) ) ) {
			return true;
		}

		if ( ( $title->getNamespace() == NS_BLOG_ARTICLE_TALK ) && ( empty($wgEnableBlogCommentEdit) ) ) {
			return true;
		}

		$row = array();
		switch( $wgTitle->getNamespace()  ) {
			case NS_BLOG_ARTICLE:
				if ( !$title->isSubpage() ) {
					$allowedTabs = array();
					$tabs = array();
					break;
				}
			case NS_BLOG_LISTING:
				if (empty($wgEnableSemanticMediaWikiExt)) {
					$row["listing-refresh-tab"] = array(
						"class" => "",
						"text" => wfMessage( "blog-refresh-label" )->text(),
						"icon" => "refresh",
						"href" => $title->getLocalUrl( "action=purge" )
					);
					$tabs += $row;
				}
				break;
			case NS_BLOG_ARTICLE_TALK: {
				$allowedTabs = array('viewsource', 'edit', 'delete', 'history');
				foreach ( $tabs as $key => $tab ) {
					if ( !in_array($key, $allowedTabs) ) {
						unset($tabs[$key]);
					}
				}
				break;
			}
		}


		return true;
	}

	/**
	 * write additinonal checkboxes on editpage
	 */
	static public function editPageCheckboxes( &$EditPage, &$checkboxes ) {

		if( $EditPage->mTitle->getNamespace() != NS_BLOG_ARTICLE ) {
			return true;
		}
		wfProfileIn( __METHOD__ );
		Wikia::log( __METHOD__ );

		$output = array();
		if( $EditPage->mTitle->mArticleID ) {
			$props = self::getProps( $EditPage->mTitle->mArticleID );
			$output["voting"] = Xml::checkLabel(
				wfMessage( "blog-voting-label" )->text(),
				"wpVoting",
				"wpVoting",
				isset( $props["voting"] ) && $props[ "voting" ] == 1
			);
			$output["commenting"] = Xml::checkLabel(
				wfMessage( "blog-comments-label" )->text(),
				"wpCommenting",
				"wpCommenting",
				isset( $props["commenting"] ) && $props[ "commenting"] == 1
			);
		}
		$checkboxes += $output;
		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * store properties for updated article
	 */
	static public function linksUpdate( &$LinksUpdate ) {

		$namespace = $LinksUpdate->mTitle->getNamespace();
		if( !in_array( $namespace, array( NS_BLOG_ARTICLE, NS_BLOG_ARTICLE_TALK ) ) ) {
			return true;
		}

		wfProfileIn( __METHOD__ );
		$request = $this->getContext()->getRequest();

		/**
		 * restore/change properties for blog article
		 */
		$pageId = $LinksUpdate->mTitle->getArticleId();
		$keep   = array();

		if( $request->wasPosted() ) {
			$keep[ "voting" ]     = $request->getVal( "wpVoting", 0 );
			$keep[ "commenting" ] = $request->getVal( "wpCommenting", 0 );
		}
		else {
			/**
			 * read current values from database
			 */
			$props = self::getProps( $pageId );
			switch( $namespace ) {
				case NS_BLOG_ARTICLE:
					$keep[ "voting" ]     = isset( $props["voting"] ) ? $props["voting"] : 0;
					$keep[ "commenting" ] = isset( $props["commenting"] ) ? $props["commenting"] : 0;
					break;

				case NS_BLOG_ARTICLE_TALK:
					$keep[ "hiddencomm" ] = isset( $props["hiddencomm"] ) ? $props["hiddencomm"] : 0;
					break;
			}
		}

		if( $pageId ) {
			$LinksUpdate->mProperties += $keep;
		}

		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * guess Owner of blog from title
	 *
	 * @static
	 * @access public
	 *
	 * @return String -- guessed name
	 */
	static public function getOwner( $title ) {
		wfProfileIn( __METHOD__ );
		if( $title instanceof Title ) {
			$title = $title->getBaseText();
		}
		if( strpos( $title, "/" ) !== false ) {
			list( $title, $rest) = explode( "/", $title, 2 );
		}
		wfProfileOut( __METHOD__ );

		return $title;
	}

	/**
	 * guess Owner of blog from title and return Title instead of string
	 *
	 * @static
	 * @access public
	 *
	 * @return String -- guessed name
	 */
	static public function getOwnerTitle( $title ) {
		wfProfileIn( __METHOD__ );

		$owner = false;

		if( $title instanceof Title ) {
			$text = $title->getBaseText();
		}
		if( strpos( $text, "/" ) !== false ) {
			list( $owner, $rest) = explode( "/", $text, 2 );
		}
		wfProfileOut( __METHOD__ );

		return ( $owner ) ? Title::newFromText( $owner, NS_BLOG_ARTICLE ) : false;
	}


	/**
	 * wfMaintenance -- wiki factory maintenance
	 *
	 * @static
	 */
	static public function wfMaintenance() {
		echo "Blog Article maintenance.\n";
		/**
		 * create Blog:Recent posts page if not exists
		 */
		$recentPosts = wfMessage( "create-blog-post-recent-listing" )->text();
		if( $recentPosts ) {
			echo "Creating {$recentPosts}";
			$oTitle = Title::newFromText( $recentPosts,  NS_BLOG_LISTING );
			if( $oTitle ) {
				$oArticle = new Article( $oTitle, 0 );
				if( !$oArticle->exists( ) ) {
					$oArticle->doEdit(
						'<bloglist summary="true" count=50><title>'
						. wfMessage( "create-blog-post-recent-listing-title" )->text()
						.'</title><type>plain</type><order>date</order></bloglist>',
						wfMessage( "create-blog-post-recent-listing-log" )->text(),
						EDIT_NEW | EDIT_MINOR | EDIT_FORCE_BOT  # flags
					);
					echo "... done.\n";
				}
				else {
					echo "... already exists.\n";
				}
				/**
				 * Edit sidebar, add link to recent blog posts
				 */
				echo "Updating Monaco-sidebar";
				$sidebar = wfMessage( 'Monaco-sidebar' )->text();
				$newline = sprintf("\n* %s|%s", $oTitle->getPrefixedText(), wfMessage( "create-blog-post-recent-listing-title ")->text() );
				if( strpos( $sidebar, $newline ) !== false ) {
					$sidebar .= $newline;
					$msgTitle = Title::newFromText( 'Monaco-sidebar', NS_MEDIAWIKI );
					if( $msgTitle ) {
						$oArticle = new Article( $msgTitle, 0 );
						$oArticle->doEdit(
							$sidebar,
							wfMessage( "create-blog-post-recent-listing-log" )->text(),
							EDIT_MINOR | EDIT_FORCE_BOT  # flags
						);
					}
					echo "... done.\n";
				}
				else {
					echo "... already added.\n";
				}

			}
		}

		/**
		 * create Category:Blog page if not exists
		 */
		$catName = wfMessage( "create-blog-post-category" )->text();
		if( $catName && $catName !== "-" ) {
			echo "Creating {$catName}";
			$oTitle = Title::newFromText( $catName, NS_CATEGORY );
			if( $oTitle ) {
				$oArticle = new Article( $oTitle, 0 );
				if( !$oArticle->exists( ) ) {
					$oArticle->doEdit(
						wfMessage( "create-blog-post-category-body" )->text(),
						wfMessage( "create-blog-post-category-log" )->text(),
						EDIT_NEW | EDIT_MINOR | EDIT_FORCE_BOT  # flags
					);
					echo "... done.\n";
				}
				else {
					echo "... already exists.\n";
				}
			}
		}
	}

	/**
	 * auto-unwatch all comments if blog post was unwatched
	 *
	 * @access public
	 * @static
	 */
	static public function UnwatchBlogComments($oUser, $oArticle) {
		wfProfileIn( __METHOD__ );

		if ( wfReadOnly() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		/* @var $oUser User */
		if ( !$oUser instanceof User ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		/* @var $oArticle WikiPage */
		if ( !$oArticle instanceof Article ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		/* @var $oTitle Title */
		$oTitle = $oArticle->getTitle();
		if ( !$oTitle instanceof Title ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$list = array();
		$dbr = wfGetDB( DB_SLAVE );
		$like = $dbr->buildLike( sprintf( "%s/", $oTitle->getDBkey() ), $dbr->anyString() );
		$res = $dbr->select(
			'watchlist',
			'*',
			array(
				'wl_user' => $oUser->getId(),
				'wl_namespace' => NS_BLOG_ARTICLE_TALK,
				"wl_title $like",
			),
			__METHOD__
		);
		if( $res->numRows() > 0 ) {
			while( $row = $res->fetchObject() ) {
				$oCommentTitle = Title::makeTitleSafe( $row->wl_namespace, $row->wl_title );
				if ( $oCommentTitle instanceof Title )
					$list[] = $oCommentTitle;
			}
			$dbr->freeResult( $res );
		}

		if ( !empty($list) ) {
			foreach ( $list as $oCommentTitle ) {
				$oWItem = WatchedItem::fromUserTitle( $oUser, $oCommentTitle );
				$oWItem->removeWatch();
			}
			$oUser->invalidateCache();
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/* hook used to redirect to custom edit page */

	public static function alternateEditHook(EditPage $oEditPage) {
		$output = $this->getContext()->getOutput();
		$request = $this->getContext()->getRequest();
		$oTitle = $oEditPage->mTitle;
		if($oTitle->getNamespace() == NS_BLOG_LISTING) {
			$oSpecialPageTitle = Title::newFromText('CreateBlogListingPage', NS_SPECIAL);
			$output->redirect($oSpecialPageTitle->getFullUrl("article=" . urlencode($oTitle->getText())));
		}
		if($oTitle->getNamespace() == NS_BLOG_ARTICLE && $oTitle->isSubpage() && empty($oEditPage->isCreateBlogPage) ) {
			$oSpecialPageTitle = Title::newFromText('CreateBlogPage', NS_SPECIAL);
			if ($request->getVal('oldid')) {
				$url = $oSpecialPageTitle->getFullUrl("pageId=" . $oTitle->getArticleId() . "&oldid=" . $request->getVal('oldid'));
			}
			elseif ($request->getVal('undoafter') && $request->getVal('undo')) {
				$url = $oSpecialPageTitle->getFullUrl("pageId=" . $oTitle->getArticleId() . "&undoafter=" . $request->getVal('undoafter') . "&undo=" . $request->getVal('undo'));
			}
			else {
				$url = $oSpecialPageTitle->getFullUrl("pageId=" . $oTitle->getArticleId() );
			}
			$output->redirect($url);

		}
		return true;
	}
}
