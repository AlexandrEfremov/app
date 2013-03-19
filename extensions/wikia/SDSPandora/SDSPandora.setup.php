<?php
/**
 * Pandora project
 *
 * extension for using external structured data storage
 *
 * @author Adam Robak <adamr@wikia-inc.com>
 * @author Adrian 'ADi' Wieczorek <adi@wikia-inc.com>
 * @author Jacek Jursza <jacek@wikia-inc.com>
 * @author Jacek 'mech' Woźniak <mech@wikia-inc.com>
 * @author Rafał Leszczyński <rafal@wikia-inc.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @package MediaWiki
 *
 */


$wgExtensionCredits['other'][] = array(
	'name' => 'Pandora',
	'author' => array( 'Adrian \'ADi\' Wieczorek', 'Jacek Jursza', 'Jacek \'mech\' Woźniak', 'Rafał Leszczyński', 'Adam Robak' ),
	'descriptionmsg' => 'pandora-desc',
);

$app = F::app();
$dir = dirname(__FILE__) . '/';

/**
 * classes
 */
$app->registerClass( 'Pandora', $dir . 'Pandora.class.php' );
$app->registerClass( 'PandoraSDSObject', $dir . 'PandoraSDSObject.class.php' );
$app->registerClass( 'PandoraJsonLD', $dir . 'PandoraJsonLD.class.php' );
$app->registerClass( 'PandoraAPIClient', $dir . 'PandoraAPIClient.class.php' );
$app->registerClass( 'PandoraResponse', $dir . 'PandoraResponse.class.php' );
$app->registerClass( 'PandoraORM', $dir . 'PandoraORM.class.php' );
$app->registerClass( 'VideoObject', $dir . 'mappers/VideoObject.class.php' );

/**
 * hooks
 */

/**
 * controllers
 */

/**
 * special pages
 */

/**
 * access rights
 */

/**
 * DI setup
 */

/**
 * message files
 */
$app->registerExtensionMessageFile('Pandora', $dir . 'Pandora.i18n.php' );

Pandora::$config['current_collection_name'] = $app->wg->DBname;
