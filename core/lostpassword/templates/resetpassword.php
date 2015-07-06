<?php
/** @var array $_ */
/** @var $l OC_L10N */
style('lostpassword', 'resetpassword');
script('core', [
        'jquery-showpassword',
        'lostpassword'
]);
?>

<form action="<?php print_unescaped($_['link']) ?>" id="reset-password" method="post" autocomplete="off">
	<fieldset>
		<p>
			<input type="password" style="display:none">
			<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
			<input type="password" name="password" id="password" value="" placeholder="<?php p($l->t('New Password')); ?>" required data-typetoggle="#password-show"/>
			<img class="svg" id="password-icon" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
                        <input id="password-show" type="checkbox" name="show"></input>
                        <label for="password-show"></label>
		</p>
		<input type="submit" id="submit" value="<?php p($l->t('Reset password')); ?>" />
		<p class="text-center">
			<img class="hidden" id="float-spinner" src="<?php p(\OCP\Util::imagePath('core', 'loading-dark.gif'));?>"/>
		</p>
	</fieldset>
</form>
