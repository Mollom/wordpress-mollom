# Mollom plugin string localizations

This directory contains gettext string localizations for the Mollom plugin.  Quick-start:

* Original English strings are contained in `mollom.pot`.
* Filenames of translations must be in the format `mollom-[locale].[mo|po]`.
    * `[locale]` is e.g. `de_DE`, `da_DK`, `en_UK`, etc.
* WordPress only reads `.mo` files at runtime; use a [translation tool] (e.g., PoEdit) to compile `.po` into `.mo`.

## Contributing translations

1. Use a [translation tool] to translate the original strings in `mollom.pot` into your language.
1. Create an issue in the [issue queue] and attach both the `.po` and `.mo` file for inclusion.
    * Bonus points for creating an actual pull request :)
1. A maintainer will review and include your translation into the project.


## Maintainer procedure

1. Sync/commit latest code to WordPress.org SVN trunk.
1. [Regenerate](http://wordpress.org/plugins/mollom/admin/?action=pot) the `mollom.pot` file.
1. Commit to GitHub.
1. Sync/commit to WordPress.org SVN trunk again.
1. Ideally: Announce update and wait for translations.
1. Proceed with release.


[translation tool]: http://codex.wordpress.org/Translating_WordPress
[issue queue]: https://github.com/Mollom/wordpress-mollom/issues
