<?php

class HubRssFeedSpecialController extends WikiaSpecialPageController {
	const SPECIAL_NAME = 'HubRssFeed';
	const CACHE_KEY = 'HubRssFeed';
	const CACHE_TIME = 3600;
	const DAY_QUARTER = 21600;
	const CACHE_10MIN = 600;
	/** Use it after release to generate new memcache keys. */
	const CACHE_BUST = 26;

	protected $hubs = [
		'gaming' => WikiFactoryHub::CATEGORY_ID_GAMING,
		'entertainment' => WikiFactoryHub::CATEGORY_ID_ENTERTAINMENT,
		'lifestyle' => WikiFactoryHub::CATEGORY_ID_LIFESTYLE
	];

	protected $customFeeds = [
		TvRssModel::FEED_NAME=>true
	];

	/**
	 * @var HubRssFeedModel
	 */
	protected $model;

	/**
	 * @var Title
	 */
	protected $currentTitle;

	/**
	 * @param \HubRssFeedModel $model
	 */
	public function setModel( $model ) {
		$this->model = $model;
	}

	/**
	 * @return \HubRssFeedModel
	 */
	public function getModel() {
		return $this->model;
	}


	public function __construct() {
		parent::__construct( self::SPECIAL_NAME, self::SPECIAL_NAME, false );
		$this->currentTitle = SpecialPage::getTitleFor( self::SPECIAL_NAME );
	}


	public function notfound() {
		$url = $this->currentTitle->getFullUrl();
		$links = [];

		foreach ( $this->hubs as $k => $v ) {
			$links[ ] = $url . '/' . ucfirst( $k );
		}

		$this->setVal( 'links', $links );
		$this->wg->SupressPageSubtitle = true;

	}


	public function index() {
		global $wgHubRssFeedCityIds;

		$params = $this->request->getParams();

		$hubName = strtolower( (string)$params[ 'par' ] );

		if ( !isset( $this->hubs[ $hubName ] ) ) {
			if ( isset( $this->customFeeds[$hubName] ) ) {
				return $this->forward( 'HubRssFeedSpecial', 'customRss' );
			}
			return $this->forward( 'HubRssFeedSpecial', 'notfound' );
		}

		$langCode = $this->app->wg->ContLang->getCode();
		$this->model = new HubRssFeedModel($langCode);

		$memcKey = wfMemcKey( self::CACHE_KEY, $hubName, self::CACHE_BUST, $langCode );

		$xml = $this->wg->memc->get( $memcKey );
		if ( $xml === false ) {
			$service = new HubRssFeedService($langCode, $this->currentTitle->getFullUrl() . '/' . ucfirst( $hubName ));
			$verticalId = $this->hubs[ $hubName ];
			$cityId = isset( $wgHubRssFeedCityIds[ $hubName ] ) ? $wgHubRssFeedCityIds[ $hubName ] : 0;
			$data = array_merge( $this->model->getRealDataV3( $cityId ), $this->model->getRealDataV2( $verticalId ) );
			$xml = $service->dataToXml( $data, $verticalId );
			$this->wg->memc->set( $memcKey, $xml, self::CACHE_TIME );
		}

		$this->response->setFormat( WikiaResponse::FORMAT_RAW );
		$this->response->setBody( $xml );
		$this->response->setContentType( 'text/xml' );
	}

	public function customRss() {
		$service = new RssFeedService();
		$par = $this->request->getVal( 'par' );
		$model = BaseRssModel::newFromName( $par );
		$service->setFeedLang( $model->getFeedLanguage() );
		$service->setFeedDescription( $model->getFeedDescription() );
		$service->setFeedUrl( SpecialPage::getTitleFor( self::SPECIAL_NAME )->getFullUrl() . ucfirst( $par ) );
		$service->setData( $model->getFeedData() );
		$this->response->setFormat( WikiaResponse::FORMAT_RAW );
		$this->response->setBody( $service->toXml() );
		$this->response->setContentType( 'text/xml' );
		$this->response->setCacheValidity( /*WikiaResponse::CACHE_SHORT*/ 1 );
	}

	public function gamesRssTV() {
		$cacheKey = 'test-rss-games';

		$feedModel = new \Wikia\Search\Services\FeedEntitySearchService();
		$m = new MixedFeedModel();
		$feedModel->setSorts(['created'=>'desc']);
		$rows = $feedModel->query( '(+host:"dragonage.wikia.com" AND +categories_mv_en:"News")
		| (+host:"warframe.wikia.com" AND +categories_mv_en:"Blog posts")
		| (+host:"monsterhunter.wikia.com" AND +categories_mv_en:"News")
		| (+host:"darksouls.wikia.com" AND +categories_mv_en:"News")
		| (+host:"halo.wikia.com" AND +categories_mv_en:"Blog_posts/News")
		| (+host:"gta.wikia.com" AND +categories_mv_en:"News")
		| (+host:"fallout.wikia.com" AND +categories_mv_en:"News")
		| (+host:"elderscrolls.wikia.com" AND +categories_mv_en:"News")
		| (+host:"leagueoflegends.wikia.com" AND +categories_mv_en:"News_blog")' );
		$data = [];

		$model = new HubRssFeedModel('en');
		$v3 = $model->getRealDataV3( 955764 );

		$f2 = new \Wikia\Search\Services\FeedEntitySearchService();
		if(!empty($v3)){
			$f2->setUrls(array_keys($v3));
			$res = $f2->query('');
			foreach($res as $item){
				$item['hub'] = true;
				$data[$item['url']] = $item;
			}
		}
		$body = $this->wg->memc->get($cacheKey);
		if(!$body){
			$body = [];
		}
		foreach ( $rows as $item ) {
			if(array_key_exists($item[ 'url' ],$body)){
				$data[ $item[ 'url' ] ]  = $body[$item[ 'url' ]];
			}else{
				$ids = explode( '_', $item[ 'id' ] );
				$item[ 'img' ] = $m->getArticleThumbnail( $item[ 'host' ], $ids[ 0 ], $ids[ 1 ] );
				$item[ 'description' ] = $m->getArticleDescription( $item[ 'host' ], $ids[ 1 ] );
				$data[ $item[ 'url' ] ] = $item;
			}
		}
		$this->wg->memc->set($cacheKey, $data);
		$timestamps = [];
		foreach ($data as $key => $row) {
			$timestamps[$key]  = $row['timestamp'];
		}

		array_multisort($timestamps, SORT_DESC, $data);
		foreach($data as &$item){
			$pos = strrpos($item['title'],'/');
			if($pos){
				$pos ++;
			}
			$title = substr($item['title'],$pos );

			$item['title'] = $item['wikititle'] . ' - '.$title;

		}
		//var_dump($data);

		$service = new HubRssFeedService('en', SpecialPage::getTitleFor( self::SPECIAL_NAME )->getFullUrl() . "/Games" );

		$xml = $service->dataToXml( $data, MixedFeedModel::FAKE_HUB_ELDERSCROLLS );
		$this->response->setFormat( WikiaResponse::FORMAT_RAW );
		$this->response->setBody( $xml );
		$this->response->setContentType( 'text/xml' );
		$this->response->setCacheValidity( WikiaResponse::CACHE_SHORT );
	}

	public function customRssTV() {

		$body = $this->wg->memc->get("test-rss-tv2");
		if ( 0 && $body ) {
			$this->response->setFormat( WikiaResponse::FORMAT_RAW );
			$this->response->setBody( $body );
			$this->response->setContentType( 'text/xml' );
		} else {

			$rssService = new RssFeedService();
			$tvPremieres = new TVEpisodePremiereService();

			$rssService->setFeedTitle("Wikia TV");
			$rssService->setFeedDescription("Tv episodes");
			$rssService->setFeedUrl(SpecialPage::getTitleFor( self::SPECIAL_NAME )->getFullUrl() . "/Tv");
			$epizodes = $tvPremieres->getTVEpisodes();
			$data = $tvPremieres->getWikiaArticles( $epizodes );

			$wikisFound = [];
			foreach ( $data as $ep ) {
				if ( isset($ep['wikia']) ) {
					$wikisFound[$ep['wikia']['wikiId']] = $ep['wikia']['wikiId'];
				}
			}
			$wikisFound = array_keys($wikisFound);
			$numWikiFound = count($wikisFound);
			foreach ( $data as $ep ) {
				if ( isset( $ep['wikia'] ) ) {

					$details = $rssService->getArticleDetails(
						$ep['wikia']['wikiId'],
						$ep['wikia']['articleId'],
						$ep['wikia']['url']
					);

					$abstract = $details->items->{$ep['wikia']['articleId']}->abstract;
					$timestamp = $details->items->{$ep['wikia']['articleId']}->revision->timestamp;
					$thumb = $details->items->{$ep['wikia']['articleId']}->thumbnail;

					$thumbA = null;
					if ( !empty($thumb) ) {
						$thumbA['url'] = $thumb;
						$thumbA['width'] = $details->items->{$ep['wikia']['articleId']}->original_dimensions->width;
						$thumbA['height'] = $details->items->{$ep['wikia']['articleId']}->original_dimensions->height;
					}
					if ( $details ) {
						$rssService->addElem(
							"New episode from " . $ep['title'].': '.$ep['episode_title'],
							$abstract,
							str_replace("jacek.wikia-dev", "wikia", $ep['wikia']['url']),
							$timestamp,
							$thumbA
						);
					}
				}
			}


			if ( $numWikiFound < 5 && $numWikiFound > 0 ) {
				foreach ($wikisFound as $wikiId) {
					$other = $tvPremieres->otherArticles($wikiId);
					if ( isset($other[1]) ) {
						$details = $rssService->getArticleDetails(
							$wikiId,
							$other[1][0]['article_id'],
							$other[1][0]['url']
						);

						$thumb = $details->items->{$other[1][0]['article_id']}->thumbnail;
						$thumbA = null;
						if ( !empty($thumb) ) {
							$thumbA['url'] = $thumb;
							$thumbA['width'] = $details->items->{$other[1][0]['article_id']}->original_dimensions->width;
							$thumbA['height'] = $details->items->{$other[1][0]['article_id']}->original_dimensions->height;
						}

						$abstract = $details->items->{$other[1][0]['article_id']}->abstract;
						$timestamp = $details->items->{$other[1][0]['article_id']}->revision->timestamp;
						if ( $details ) {
							$rssService->addElem(
								$details->items->{$other[1][0]['article_id']}->title,
								$abstract,
								str_replace("jacek.wikia-dev", "wikia", $other[1][0]['url']),
								$timestamp,
								$thumbA
							);
						}
					}
				}

			}


			$body = $rssService->toXml();
			$this->wg->memc->set("test-rss-tv2", $body);

			$this->response->setFormat( WikiaResponse::FORMAT_RAW );
			$this->response->setBody( $body );
			$this->response->setContentType( 'text/xml' );
			$this->response->setCacheValidity( WikiaResponse::CACHE_SHORT );
		}
		return $data;
	}


}
