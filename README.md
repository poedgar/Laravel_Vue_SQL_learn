## Formatting

## Formatting a Laravel + Vue Project in VS Code

Here's a complete setup for proper formatting:

---

### 1. Install Required VS Code Extensions

**Essential:**

- **PHP Intelephense** or **PHP CS Fixer** — PHP/Laravel formatting
- **Volar** (Vue - Official) — Vue 3 support _(disable Vetur if installed)_
- **ESLint** — JavaScript/Vue linting
- **Prettier - Code formatter** — unified formatting
- **EditorConfig for VS Code** — consistent editor settings

---

### 2. Install Project Dependencies

```bash
# ESLint + Prettier for JS/Vue
npm install --save-dev eslint prettier eslint-plugin-vue @vue/eslint-config-prettier

# PHP CS Fixer (via Composer)
composer require --dev friendsofphp/php-cs-fixer
```

---

### 3. Configure ESLint

Create `.eslintrc.js` in your project root:

```js
module.exports = {
  root: true,
  env: { browser: true, es2021: true, node: true },
  extends: ['plugin:vue/vue3-recommended', '@vue/eslint-config-prettier'],
  rules: {
    'vue/multi-word-component-names': 'off',
    'prettier/prettier': 'error',
  },
};
```

---

### 4. Configure Prettier

Create `.prettierrc` in your project root:

```json
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 100,
  "vueIndentScriptAndStyle": true
}
```

---

### 5. Configure PHP CS Fixer

Create `.php-cs-fixer.php` in your project root:

```php
<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude(['vendor', 'node_modules', 'storage', 'bootstrap/cache'])
    );
```

---

### 6. VS Code Settings

Create or update `.vscode/settings.json`:

```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",

  "[php]": {
    "editor.defaultFormatter": "junstyle.php-cs-fixer"
  },
  "[vue]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode"
  },
  "[javascript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode"
  },

  "php-cs-fixer.executablePath": "${workspaceFolder}/vendor/bin/php-cs-fixer",
  "php-cs-fixer.onsave": true,
  "php-cs-fixer.rules": "@PSR12",

  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": "explicit"
  },

  "eslint.validate": ["javascript", "vue"],
  "volar.completion.autoImportComponent": true
}
```

---

### 7. EditorConfig (optional but recommended)

Create `.editorconfig` in your project root:

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 2
insert_final_newline = true
trim_trailing_whitespace = true

[*.php]
indent_size = 4
```

---

### Quick Tips

| Issue                           | Fix                                                                  |
| ------------------------------- | -------------------------------------------------------------------- |
| Vue file not formatting         | Make sure **Volar** is active, not Vetur                             |
| PHP not formatting on save      | Check the `php-cs-fixer.executablePath` points to your vendor binary |
| ESLint and Prettier conflicting | Use `eslint-config-prettier` to turn off conflicting ESLint rules    |
| Blade files not formatting      | Install the **Laravel Blade formatter** extension separately         |

This setup gives you **format-on-save** for PHP, Vue, and JS files with consistent style across your whole team.
