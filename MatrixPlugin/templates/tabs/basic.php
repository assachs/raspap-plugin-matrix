<div class="tab-pane active" id="matrixsettings">
    <h4 class="mt-3"><?php echo _("Basic settings"); ?></h4>
    <div class="row">
        <div class="mb-3 col-12 mt-2">
            <div class="row">
                <div class="col-12">
                    <?php echo htmlspecialchars($content); ?>
                </div>
            </div>
            <div class="row mt-3">
                <div class="mb-3 col-md-6" required>
                    <div class="input-group">
                        <div class="form-check form-switch">
                            <?php $checked = $__template_data['onboot'] == 1 ? 'checked="checked"' : '' ?>
                            <input class="form-check-input" id="onboot" name="onboot" type="checkbox" value="1" <?php echo $checked ?> />
                            <label class="form-check-label" for="onboot"><?php echo _("Autostart") ?></label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div><!-- /.tab-pane | basic tab -->

