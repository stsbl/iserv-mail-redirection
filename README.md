# IServ Mail Redirection

The `stsbl/iserv-mail-redirection` Portal-Web v4 module manages local mail
aliases and their user and group recipients. It exposes aliases to the IServ
mail autocomplete source and synchronizes recipient account metadata from IDM.

## Development

Install the module on an IServ development system from the project root:

```sh
iservmake iservinstall
iservchk
```

Run the migration's quality checks with:

```sh
iservmake run_tools
```

The declarative database schema is in `db/mailredirection.sql`; apply schema
changes through `iservchk`/`chkdb`, never with manual SQL. The recipient join
tables deliberately remain because Exim needs the local mail account data.
