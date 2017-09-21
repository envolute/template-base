<?php
/* SISTEMA PARA CADASTRO DE TELEFONES
 * AUTOR: IVO JUNIOR
 * EM: 18/02/2016
*/
defined('_JEXEC') or die;
$ajaxRequest = false;
require('config.php');

// ACESSO
$cfg['isPublic'] = true; // Público -> acesso aberto a todos

// IMPORTANTE: Carrega o arquivo 'helper' do template
JLoader::register('baseHelper', JPATH_CORE.DS.'helpers/base.php');
JLoader::register('baseAppHelper', JPATH_CORE.DS.'helpers/apps.php');

$app = JFactory::getApplication('site');

// GET CURRENT USER'S DATA
$user = JFactory::getUser();
$groups = $user->groups;

// init general css/js files
require(JPATH_CORE.DS.'apps/_init.app.php');

// Get request data
$p = $app->input->get('p', 0, 'int');

// Carrega o arquivo de tradução
// OBS: para arquivos externos com o carregamento do framework '_init.joomla.php' (geralmente em 'ajax')
// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
// Para possibilitar o carregamento da language 'default' de forma dinâmica,
// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
if(isset($_SESSION[$APPTAG.'langDef'])) :
	$lang->load('base_apps', JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
	$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
endif;

if($p != 0) :

	// DATABASE CONNECT
	$db = JFactory::getDbo();

	// GET DATA
	$query	= '
		SELECT
			T1.*,
			'. $db->quoteName('T2.name') .' grp
		FROM '.
			$db->quoteName($cfg['mainTable']) .' T1
			JOIN '. $db->quoteName($cfg['mainTable'].'_groups') .' T2
			ON T2.id = T1.group_id AND T2.state = 1
		WHERE
			'. $db->quoteName('T1.id') .' = '. $p .' AND
			'. $db->quoteName('T1.agreement') .' = 1
	';
	try {
		$db->setQuery($query);
		$item = $db->loadObject();
	} catch (RuntimeException $e) {
		echo $e->getMessage();
		return;
	}

	$provider = '';
	if(!empty($item->name)) : // verifica se existe

		// RELATIONS
		$rel = '';

		// Telefones
		$query	= '
			SELECT *
			FROM '.$db->quoteName('#__'.$cfg['project'].'_phones') .' T1
				JOIN '. $db->quoteName('#__'.$cfg['project'].'_rel_providers_phones') .' T2
				ON '.$db->quoteName('T2.phone_id') .' = T1.id
			WHERE '.$db->quoteName('T2.provider_id') .' = '. $p .'
			ORDER BY '.$db->quoteName('T1.main') .' DESC
		';
		try {
			$db->setQuery($query);
			$db->execute();
			$num_rows = $db->getNumRows();
			$res = $db->loadObjectList();
		} catch (RuntimeException $e) {
			echo $e->getMessage();
			return;
		}

		if($num_rows) : // verifica se existe
			$rel .= '<h6 class="page-header mb-0 base-icon-phone-squared"> '.JText::_('TEXT_PROVIDER_PHONES').'</h6>';
			$rel .= '<ul class="set-list bordered list-striped list-hover mb-4">';
			foreach($res as $obj) {
				$wapp = $obj->whatsapp == 1 ? ' <span class="base-icon-whatsapp text-success cursor-help hasTooltip" title="'.JText::_('TEXT_HAS_WHATSAPP').'"></span>' : '';
				$rel .= '
					<li>'.$obj->phone_number.$wapp.'</li>
				';
			}
			$rel .= '</ul>';
			unset($obj); // reseta as informações contidas em item
		endif;

		// Endereços
		$query	= '
			SELECT T1.*
			FROM '.$db->quoteName('#__'.$cfg['project'].'_addresses') .' T1
				JOIN '. $db->quoteName('#__'.$cfg['project'].'_rel_providers_addresses') .' T2
				ON '.$db->quoteName('T2.address_id') .' = T1.id
			WHERE '.$db->quoteName('T2.provider_id') .' = '. $p .'
			ORDER BY '.$db->quoteName('T1.main') .' DESC
		';
		try {
			$db->setQuery($query);
			$db->execute();
			$num_rows = $db->getNumRows();
			$res = $db->loadObjectList();
		} catch (RuntimeException $e) {
			echo $e->getMessage();
			return;
		}

		if($num_rows) : // verifica se existe
			$rel .= '<h6 class="page-header mb-0 base-icon-location"> '.JText::_('TEXT_ADDRESSES').'</h6>';
			$rel .= '<ul class="set-list bordered list-striped list-hover mb-4">';
			foreach($res as $obj) {

				$main = $obj->main == 1 ? '<span class="base-icon-star text-live cursor-help hasTooltip" title="'.JText::_('TEXT_ADDRESS_MAIN').'"></span> ' : '<div class="font-weight-bold">'.baseHelper::nameFormat($obj->description).'</div>';
				$addressInfo = !empty($obj->address_info) ? ', '.$obj->address_info : '';
				$addressNumber = !empty($obj->address_number) ? ', '.$obj->address_number : '';
				$addressZip = !empty($obj->zip_code) ? $obj->zip_code.', ' : '';
				$addressDistrict = !empty($obj->address_district) ? baseHelper::nameFormat($obj->address_district) : '';
				$addressCity = !empty($obj->address_city) ? ', '.baseHelper::nameFormat($obj->address_city) : '';
				$addressState = !empty($obj->address_state) ? ', '.baseHelper::nameFormat($obj->address_state) : '';

				$rel .= '
					<li>
						'.$main.baseHelper::nameFormat($obj->address).$addressNumber.$addressInfo.'
						<div class="text-sm text-muted">'.
							$addressZip.$addressDistrict.$addressCity.$addressState.'
						</div>
					</li>
				';
			}
			$rel .= '</ul>';
			unset($obj); // reseta as informações contidas em item
		endif;

		JLoader::register('uploader', JPATH_CORE.DS.'helpers/files/upload.php');
		// Imagem Principal -> Primeira imagem (index = 0)
		$img = uploader::getFile($cfg['fileTable'], '', $item->id, 0, $cfg['uploadDir']);
		if(!empty($img)) $img = '<img src=\''.baseHelper::thumbnail('images/apps/'.$APPPATH.'/'.$img['filename'], 200, 200).'\' class=\'img-fluid float-sm-left mb-4\' />';

		$site	= !empty($item->website) ? '<a href="'.$item->website.'" class="new-window" target="_blank">'.$item->website.'</a>' : '';
		$cnpj	= !empty($item->cnpj) ? '<label class="label-sm mt-3">CNPJ</label>'.$item->cnpj : '';
		$agree	= $item->agreement == 1 ? '<span class="badge badge-success float-right">'.JText::_('FIELD_LABEL_AGREEMENT').'</span>' : '';
		// Web
		$web	= !empty($site) ? '<div class="text-md text-muted mt-2">'.$site.'</div>' : '';

		$provider .= '
			<div class="row">
				<div class="col-sm-4 col-md-3 col-xl-2 text-center text-sm-left">
					'.$img.'
					<div>'.$cnpj.'</div>
					<hr class="d-sm-none" />
				</div>
				<div class="col-sm-8 col-md-9 col-xl-10">
		';
		$provider .= '
			<h2 class="page-header mt-0">
				'.baseHelper::nameFormat($item->name).$agree.$web.'
			</h2>
		';
		$info = '';
		if(!empty($item->description)) $info .= $item->description.'<hr />';
		if(!empty($item->service_desc)) :
			$info .= '<h5 class="mt-4">'.JText::_('FIELD_LABEL_SERVICE').'</h5>';
			$info .= $item->service_desc;
		endif;


		if(!empty($rel)) :
			$provider .= '
				<div class="row">
					<div class="col-lg-8">
						'.$info.'
						<hr class="d-sm-none" />
					</div>
					<div class="col-lg-4">'.$rel.'</div>
				</div>
			';
		else :
			$provider .= $info;
		endif;

		$provider .= '
				</div>
			</div>
		';
	else :
		$provider = '<p class="base-icon-info-circled alert alert-info m-0"> '.JText::_('MSG_ITEM_NOT_AVAILABLE').'</p>';
	endif;

	echo $provider;

else :

	echo '<h4 class="alert alert-warning">'.JText::_('MSG_NOT_PROVIDER_SELECTED').'</h4>';

endif;
?>