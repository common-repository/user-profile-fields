<?php
/*
Plugin Name:   User Profile Fields
Description:   Allows you to removes the AIM, YIM, Jabber, Website, and Biography fields from the user profile page.
Version:       0.1
Author:        John Luetke
Author URI:    http://johnluetke.net
License:       Apache License 2.0
*/

//   Copyright 2013 John Luetke
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.

if (!class_exists('UserProfileFields')) {

	define("USERPROFILEFIELDS_DEFAULT_OPTIONS", serialize ( array (
			'disable_aim' => "off",
			'disable_bio' =>"off",
			'disable_jabber' => "off",
			'disable_website' => "off",		
			'disable_yim' => "off",
			'bio_title' => "Change Password"
	)));

	define("USERPROFILEFIELDS_OPTIONS", "user_profile_fields_options");

	class UserProfileFields {

		var $options;
		
		public function __construct() {
			$this->refresh();
		}

		private function refresh() {
			$this->options = array_merge(unserialize(USERPROFILEFIELDS_DEFAULT_OPTIONS), get_option(USERPROFILEFIELDS_OPTIONS));
		}

		public function removeFields($fields) {
			if ($this->options['disable_aim'] == "on") $fields = $this->removeAIM($fields);
			if ($this->options['disable_jabber'] == "on") $fields = $this->removeJabber($fields);
			if ($this->options['disable_yim'] == "on") $fields = $this->removeYIM($fields);

			return $fields;
		}

		public function removeAIM($fields) {
			return $this->removeField($fields, 'aim');
		}
	
		public function removeBio($ob_buffer) {
			$title_regex = "#<h3>(About (Yourself)?(the user)?)<\/h3>#";
			$matches = array();
			preg_match($title_regex, $ob_buffer, $matches);
			$title = $this->options['bio_title'];
			$ob_buffer = preg_replace($title_regex,'<h3>TITLE PLACEHOLDER</h3>', $ob_buffer, 1);
			$content = '#<h3>TITLE PLACEHOLDER</h3>.+?<table.+?/tr>#s';
			// TODO: User supplied title
			$ob_buffer = preg_replace($content, '<h3>'.$title.'</h3> <table class="form-table">', $ob_buffer, 1);
			return $ob_buffer;
		}

		public function removeJabber($fields) {
			return $this->removeField($fields, 'jabber');
		}

		public function removeWebsite($ob_buffer) {
			$regex = "/<tr>\n*\s*<th><label for=\"url\">Website<\/label><\/th>\n*\s*.*\n*\s*<\/tr>/";
			$ob_buffer = preg_replace($regex, "", $ob_buffer, 1);

			return $ob_buffer;	
		}

		public function removeYIM($fields) {
			return $this->removeField($fields, 'yim');
		}

		private function removeField($fields, $name) {
			unset($fields[$name]);
			return $fields;
		}

		public function registerAdminMenu() {
			add_users_page("User Profile Fields", "Profile Fields", "edit_users", __FILE__, array($this, "optionsPage"));
		}

		public function optionsPage() {
			// double check perms

			if ($_POST) {
				$_POST = array_merge(unserialize(USERPROFILEFIELDS_DEFAULT_OPTIONS), $_POST);
				//print_r($_POST);
				update_option(USERPROFILEFIELDS_OPTIONS, $_POST);
				$this->refresh();
				//print_r($this->options);
				//die();
			}

			?>
	<div class='wrap'>
		<div id="icon-options-general" class="icon32"></div>
		<h2>User Profile Fields</h2>
		<form method='post' action='<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(dirname(__FILE__)) . "/" .basename(__FILE__);?>'>
			<h3>Contact Info Fields</h3>
			<table class="form-table htauth-sync-options">
				<tr>
					<td width="25"><input type="checkbox" name="disable_aim" <?php echo $this->options['disable_aim'] == "on" ? "checked=\"checked\" " : ""; ?>/></td></td>
					<td><label for="disable_aim">Disable <strong>AIM</strong> field</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="disable_jabber" <?php echo $this->options['disable_jabber'] == "on" ? "checked=\"checked\" " : ""; ?>/></td>
					<td><label for="disable_jabber">Disable <strong>Jabber / Google Talk</strong> field</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="disable_website" <?php echo $this->options['disable_website'] == "on" ? "checked=\"checked\" " : ""; ?>/></td>
					<td><label for="disable_website">Disable <strong>Website</strong> field</label></td>
				</tr>
				<tr>
					<td><input type="checkbox" name="disable_yim" <?php echo $this->options['disable_yim'] == "on" ? "checked=\"checked\" " : ""; ?>/></td>
					<td><label for="disable_yim">Disable <strong>Yahoo! Instant Messenger</strong> field</label></td>
				</tr>
			</table>
			<h3>About Yourself Fields</h3>
			<table class="form-table">
				<tr>
					<td width="25"><input type="checkbox" name="disable_bio" <?php echo $this->options['disable_bio'] == "on" ? "checked=\"checked\" " : ""; ?>/></td>
					<td><label for="disable_bio">Disable <strong>Biographical Info</strong> field</label><br><span class="description">Will also change the section header to <strong><?php echo $this->options['bio_title']; ?></strong></span></td>
				</tr>
			</table>
			<!--<h3>New Fields</h3>
			<table class="form-table">

			</table>-->
			<input type="submit" class="button" value="Save Changes" />
		</form>
			<?php
		}
	}

	function remove_profile_fields_ob_start() {
		ob_start("remove_profile_fields_ob_helper");
	}

	function remove_profile_fields_ob_end() {
		ob_end_flush();
	}

	function remove_profile_fields_ob_helper($ob_buffer) {
		global $UserProfileFields;
		if ($UserProfileFields->options['disable_bio'] == "on") {
			$ob_buffer = $UserProfileFields->removeBio($ob_buffer);
		}
		if ($UserProfileFields->options['disable_website'] == "on") {
			$ob_buffer = $UserProfileFields->removeWebsite($ob_buffer);
		}

		return $ob_buffer;
	}

	$UserProfileFields = new UserProfileFields();
	
	add_action('admin_menu', array($UserProfileFields, "registerAdminMenu"));
	add_action('admin_head', 'remove_profile_fields_ob_start');
	add_action('admin_footer', 'remove_profile_fields_ob_end');

	add_filter( 'user_contactmethods', array($UserProfileFields, 'removeFields'), 1, 3);
}

?>

