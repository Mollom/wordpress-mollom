<?php if ($spam_classification == 'ham') : ?>

<?php _e('Ham', I18N_MOLLOM); ?> 

<?php elseif ($spam_classification == 'unsure') : ?>

<?php _e('Unsure', I18N_MOLLOM); ?>

<?php else : ?>

<?php _e('No information', I18N_MOLLOM); ?> 

<?php endif; ?>
