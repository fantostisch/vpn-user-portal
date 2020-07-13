<?php $this->layout('base', ['activeItem' => 'CR', 'pageTitle' => $this->t('Create user')]); ?>
<?php $this->start('content'); ?>
<form method="post" class="frm" action="/createclient" >
            <fieldset>
                </select>
                <label for="displayName"><?=$this->t('Name'); ?></label>
                <input type="text" name="displayName" id="displayName" size="32" maxlength="64" placeholder="<?=$this->t('Name'); ?>" autofocus required>
                <label for="displayName"><?=$this->t('Info'); ?></label>
                <input type="text" name="displayInfo" id="displayInfo" size="32" maxlength="64" placeholder="<?=$this->t('Info'); ?>" autofocus required>
            </fieldset>
            <fieldset>
                <button type="submit"><?=$this->t('Create'); ?></button>
            </fieldset>
        </form>

<?php $this->stop('content'); ?>
