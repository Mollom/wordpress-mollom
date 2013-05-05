<?php if ($spam_classification == 'ham'): ?>
<?php _e('Ham', MOLLOM_I18N); ?> 
<?php elseif ($spam_classification == 'unsure'): ?>
<?php _e('Unsure', MOLLOM_I18N); ?>
<?php elseif ($spam_classification == 'spam'): ?>
<?php _e('Spam', MOLLOM_I18N); ?>
<?php else: ?>
<?php _e('n/a', MOLLOM_I18N); ?> 
<?php endif; ?>
