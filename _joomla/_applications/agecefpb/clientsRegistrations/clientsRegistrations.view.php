<?php
/* SISTEMA PARA CADASTRO DE TELEFONES
 * AUTOR: IVO JUNIOR
 * EM: 18/02/2016
*/
defined('_JEXEC') or die;
$ajaxRequest = false;
require('config.php');
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
$uID = $app->input->get('uID', 0, 'int');
$uID = ($hasAdmin && $uID > 0) ? $uID : $user->id;

// LINK TO EDIT
$urlEdit = JURI::root().'user/edit-profile'.($uID != $user->id ? '?uID='.$uID : '');

// Carrega o arquivo de tradução
// OBS: para arquivos externos com o carregamento do framework '_init.joomla.php' (geralmente em 'ajax')
// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
// Para possibilitar o carregamento da language 'default' de forma dinâmica,
// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
if(isset($_SESSION[$APPTAG.'langDef'])) :
	$lang->load('base_apps', JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
	$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
endif;

// Admin Actions
require_once(JPATH_APPS.DS.'clients/clients.select.php');

if(isset($user->id) && $user->id) :

	// DATABASE CONNECT
	$db = JFactory::getDbo();

	// GET DATA
	$query = 'SELECT * FROM '.$db->quoteName($cfg['mainTable']).' T1 WHERE '.$db->quoteName('T1.user_id') .' = '. $uID;
	try {
		$db->setQuery($query);
		$item = $db->loadObject();
	} catch (RuntimeException $e) {
		echo $e->getMessage();
		return;
	}

	$html = '';
	if(!empty($item->name)) : // verifica se existe

		if($cfg['hasUpload']) :
			JLoader::register('uploader', JPATH_CORE.DS.'helpers/files/upload.php');
			// Imagem Principal -> Primeira imagem (index = 0)
			$img = uploader::getFile($cfg['fileTable'], '', $item->id, 0, $cfg['uploadDir']);
			if(!empty($img)) $img = '<img src="'.baseHelper::thumbnail('images/apps/'.$APPPATH.'/'.$img['filename'], 300, 300).'" class="img-fluid b-all b-dashed p-1" />';
			else $img = '<div class="image-file"><div class="image-action"><div class="image-file-label"><span class="base-icon-file-image"></span></div></div></div>';
		endif;

		$partner = '';
		if(!empty($item->partner)) :
			$partner = '
				<div class="col">
					<label class="label-sm">'.JText::_('FIELD_LABEL_PARTNER').':</label><p>'.baseHelper::nameFormat($item->partner).'</p>
				</div>
			';
		endif;
		$phone = explode(',', $item->phones);
		$phones = !empty($phone[0]) ? $phone[0] : '';
		$phones .= !empty($phone[1]) ? (!empty($phones) ? '<br />' : '').$phone[1] : '';
		$phones .= !empty($phone[2]) ? (!empty($phones) ? '<br />' : '').'(fixo) '.$phone[2] : '';

		$html .= '
			<div class="row">
				<div class="col-md-8">
					<div class="row">
						<div class="col-sm-3 mb-4 mb-md-0">
							<a href="'.$urlEdit.'" class="btn btn-block btn-default b-2 text-live base-icon-pencil float-md-right mb-2"> '.JText::_('TEXT_EDIT').'</a>
							<div style="max-width: 300px">'.$img.'</div>
						</div>
						<div class="col-sm-9">
							<label class="label-sm">'.JText::_('FIELD_LABEL_NAME').':</label>
							<p> '.baseHelper::nameFormat($item->name).'</p>
							<label class="label-sm">'.JText::_('FIELD_LABEL_EMAIL').':</label>
							<p>'.$item->email.'</p>
							<div class="row">
								<div class="col-sm-4">
									<label class="label-sm">'.JText::_('FIELD_LABEL_GENDER').':</label>
									<p>'.JText::_('TEXT_GENDER_'.$item->gender).'</p>
								</div>
								<div class="col-sm-4">
									<label class="label-sm">'.JText::_('FIELD_LABEL_MARITAL_STATUS').':</label>
									<p>'.JText::_('TEXT_MARITAL_STATUS_'.$item->marital_status).'</p>
								</div>
								<div class="col-sm-4">
									<label class="label-sm">'.JText::_('FIELD_LABEL_CHILDREN').':</label>
									<p>'.$item->children.'</p>
								</div>
								'.$partner.'
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="row justify-content-end">
						<div class="col-sm-3 col-md-12">
							<label class="label-sm">'.JText::_('FIELD_LABEL_BIRTHDAY').':</label>
							<p>'.baseHelper::dateFormat($item->birthday).'</p>
						</div>
						<div class="col-sm-6 col-md-12">
							<label class="label-sm">CPF:</label>
							<p>'.$item->cpf.'</p>
						</div>
						<div class="col-sm-9 col-md-12">
							<label class="label-sm">RG:</label>
							<p>'.$item->rg.' / '.$item->rg_orgao.'</p>
						</div>
					</div>
				</div>
			</div>
			<hr />
			<div class="row">
				<div class="col-sm-8">
					<label class="label-sm">'.JText::_('FIELD_LABEL_ADDRESS').':</label>
					<p>
						'.baseHelper::nameFormat($item->address).', '.$item->address_number.(!empty($item->address_info) ? ', '.$item->address_info : '').'<br />
						'.(!empty($item->zip_code) ? $item->zip_code.', ' : '').baseHelper::nameFormat($item->address_district).', '.baseHelper::nameFormat($item->address_city).'
					</p>
				</div>
				<div class="col-sm-4">
					<label class="label-sm">'.JText::_('FIELD_LABEL_PHONE').'(s):</label>
					<p>'.$phones.'</p>
				</div>
			</div>
			<div class="row">
				<div class="col-md-8">
					<hr class="hr-tag" />
					<span class="badge badge-primary">'.JText::_('TEXT_DATA_EMPLOYEE').'</span>
					<div class="row">
						<div class="col-sm-5 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_STATUS_EMPLOYEE').':</label>
							<p>'.JText::_('TEXT_CX_STATUS_'.$item->cx_status).'</p>
						</div>
						<div class="col-sm-7 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_EMAIL').':</label>
							<p>'.$item->cx_email.'</p>
						</div>
						<div class="col-sm-5 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_CODE').':</label>
							<p>'.$item->cx_code.'</p>
						</div>
						<div class="col-sm-7 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_ADMISSION_DATE').':</label>
							<p>'.baseHelper::dateFormat($item->cx_date).'</p>
						</div>
						<div class="col-sm-5 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_ROLE').':</label>
							<p>'.$item->cx_role.'</p>
						</div>
						<div class="col-sm-7 col-lg-4">
							<label class="label-sm">'.JText::_('FIELD_LABEL_SITUATED').':</label>
							<p>'.$item->cx_situated.'</p>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<hr class="hr-tag" />
					<span class="badge badge-primary">'.JText::_('TEXT_ACCOUNT_DATA').'</span>
					<label class="label-sm">Conta Bancária:</label>
					<p>
						'.JText::_('FIELD_LABEL_AGENCY').': <strong>'.$item->agency.'</strong><br />
						'.JText::_('FIELD_LABEL_ACCOUNT').': <strong>'.$item->account.'</strong><br />
						'.JText::_('FIELD_LABEL_OPERATION').': <strong>'.$item->operation.'</strong>
					</p>
				</div>
			</div>
		';
	else :
		// ACCESS
		if($hasAdmin) :
			// O perfil é visualizado apenas por associados.
			// Usuários administradores "$hasAdmin" (não associados) só podem
			// visualizar seus dados ou editar seu perfil, na administração...
			echo '<div class="alert alert-warning base-icon-attention"> '.JText::_('MSG_IS_ADMIN').'</div>';
		else :
			$app->enqueueMessage(JText::_('MSG_NOT_PERMISSION'), 'warning');
			$app->redirect(JURI::root(true));
			exit();
		endif;
	endif;

	// Mensagem de sucesso após a atualização dos dados
	if(isset($_SESSION[$APPTAG.'EditSuccess']) && $_SESSION[$APPTAG.'EditSuccess']) :
		echo '<h5 class="alert alert-success base-icon-ok"> '.JText::_('MSG_EDIT_SUCCESS').'</h5>';
		unset($_SESSION[$APPTAG.'EditSuccess']);
	endif;

	?>

	<div id="<?php echo $APPTAG?>-view-data" class="clearfix">
		<?php echo $html?>
	</div>

<?php
else :

	echo '<h4 class="alert alert-warning">'.JText::_('MSG_NOT_PERMISSION').'</h4>';

endif;
?>
