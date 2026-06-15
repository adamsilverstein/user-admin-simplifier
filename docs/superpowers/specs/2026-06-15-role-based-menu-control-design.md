# Role-based menu control — design

Issue: https://github.com/adamsilverstein/user-admin-simplifier/issues/39

## Summary

Add role-based menu visibility control alongside the existing per-user control.
An administrator chooses one of three modes:

- **Per-user only** — current behavior, default.
- **Role-based only** — visibility determined by the user's role(s).
- **Role-based with per-user overrides** — role defaults, with per-user exceptions.

No behavior changes for existing installs: the mode defaults to `per-user` and
the existing per-user option is untouched.

## Data model

Three WordPress options:

| Option | Status | Shape |
| --- | --- | --- |
| `useradminsimplifier_options` | unchanged | `user_nicename => { menuId: 0\|1, "menu-order": [...], "disable-admin-bar": 0\|1 }` |
| `useradminsimplifier_roles` | new | `role_slug => { menuId: 1, "disable-admin-bar": 1, "menu-order": [...] }` |
| `useradminsimplifier_mode` | new | `"per-user"` (default) \| `"role"` \| `"role-with-overrides"` |

Role config carries show/hide flags (top menus, submenus, admin-bar items,
`disable-admin-bar`) and an optional `menu-order` list, mirroring the per-user
shape.

## Resolution

A pure, unit-testable function holds the precedence logic:

```
uas_resolve_user_flags( array $per_user_map, array $role_maps, string $mode ) : array
```

Returns the effective `menuId => 1` map of hidden items.

- **per-user**: returns `$per_user_map` (today's behavior).
- **role**: union of all `$role_maps` — an item is hidden if **any** of the
  user's roles hides it. (Decision: most restrictive / predictable, matches the
  "simplify" intent.)
- **role-with-overrides**: start from the role union, then apply the per-user
  *explicit* keys — `1` forces hide, `0` forces show, an absent key inherits the
  role value.

A wrapper `uas_get_effective_flags_for_current_user()` gathers the inputs
(current user's per-user map, the maps for each of the user's roles, the mode)
and calls the resolver. The three existing consumers are refactored to use it:

- `uas_init()` — admin-bar disable check (`disable-admin-bar` flag).
- `uas_edit_admin_menus()` — top menus + submenus.
- `uas_edit_admin_bar_menu()` — admin-bar nodes.

`disable-admin-bar` is just another key in the map, so it resolves through the
same precedence rules.

### Menu order resolution

Ordering is a sequence, not a set, so it cannot use the union rule. A separate
helper `uas_resolve_user_menu_order( $per_user_order, $primary_role_order, $mode )`
returns the effective order list:

- **per-user**: the per-user order (today's behavior).
- **role**: the order from the user's **primary role** (WP's first role). Other
  roles do not contribute ordering.
- **role-with-overrides**: the per-user order if the user has set one, otherwise
  the primary role's order.

The resolved order is then fed to the existing `uas_apply_menu_order()`.

### Lockout safeguard

Because the `administrator` role is shared by every admin, hiding the Tools menu
or the plugin's own page for that role would lock all admins out at once. To
prevent this, the User Admin Simplifier settings page and its parent Tools menu
are **never removed for a user who can `manage_options`**, regardless of the
resolved flags. Implemented via a small `uas_is_protected_menu_item()` helper
checked in `uas_edit_admin_menus()`.

## Backend AJAX

Existing handlers (`uas_save_options`, `uas_reset_user`) are unchanged. New
handlers, all reusing the existing `uas_nonce` + `current_user_can('manage_options')`
checks and the same per-key `sanitize_key` / `intval` sanitizer:

- `uas_save_mode` — validates the value against the three allowed modes.
- `uas_save_role` — saves one role's flag map.
- `uas_reset_role` — clears one role's flag map.

## Frontend (React)

- **Mode selector** — radio group at the top; persists immediately via
  `uas_save_mode`.
- **Subject selector** — shows the user dropdown (per-user and overrides modes)
  and/or a new role dropdown (role and overrides modes). Roles come from
  `get_editable_roles()` (all editable roles, including administrator).
- **Reuse** `MenuList` and `AdminBarMenu` for both user and role editing. The
  role editor wires `MenuList`'s existing `onReorder` into the role's
  `menu-order`, giving per-role drag reordering with no new component.
- **Override mode** adds an optional tri-state control (Inherit / Show / Hide)
  to `MenuList` / `AdminBarMenu` via props (`roleDefaults`, `triState`), with a
  "from role" indicator. "Inherit" stores no key.

PHP passes additional data to the app: `roles` (`[{slug, name}]`), `roleOptions`
(the role config map), `mode`, and each user's role slugs (added to the existing
`users` entries) so the override editor can compute role defaults client-side.

## Tests

New standalone `tests/test-role-resolution.php` (matching the existing
copy-the-pure-function test style, with a `sanitize_key` stub), added to the
`test:php` script. Cases:

- per-user mode passes the per-user map through unchanged.
- role mode unions conflicting multi-role config (hide if any role hides).
- role-with-overrides: explicit user `1`/`0` overrides the role default.
- role-with-overrides: absent user key inherits the role value.
- `disable-admin-bar` resolves through the same precedence.
- menu order: role mode uses the primary role's order; override mode prefers the
  per-user order and falls back to the primary role's order.

The lockout safeguard helper (`uas_is_protected_menu_item()`) is also covered.

## Out of scope (noted, not built)

- The optional "import per-user settings into role defaults" migration helper.
