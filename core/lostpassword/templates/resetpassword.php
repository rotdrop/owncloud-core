<?php OCP\Util::addStyle('lostpassword', 'resetpassword'); ?>
<form action="<?php print_unescaped($_['link']) ?>" id="reset-password" method="post" autocomplete="off">
	<fieldset>
		<p>
			<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
			<input type="password" name="password" id="password" value="" placeholder="<?php p($l->t('New Password')); ?>" required data-typetoggle="#password-show"/>
			<img class="svg" id="password-icon" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
                        <input id="password-show" type="checkbox" name="show"></input>
                        <label for="password-show"></label>
		</p>
		<input type="submit" id="submit" value="<?php p($l->t('Reset password')); ?>" />
	</fieldset>
</form>
<?php OCP\Util::addScript('core', 'lostpassword'); ?>
