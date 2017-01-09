/**
 * Author:  Felix Jacobi <felix.jacobi@stsbl.de>
 * License: MIT license <https://opensource.org/licenses/MIT>
 * Created: 30.12.2016
 */

CREATE TABLE mailredirection_addresses (
    id                  SERIAL          PRIMARY KEY,
    recipient           VARCHAR(255)    NOT NULL UNIQUE,
    enabled             BOOLEAN         NOT NULL,
    comment             TEXT,
    CHECK (recipient != 'root'),
    CHECK (recipient != 'postmaster'),
    CHECK (recipient != 'mailer-daemon'),
    CHECK (recipient != 'nobody'),
    CHECK (recipient != 'hostmaster'),
    CHECK (recipient != 'usenet'),
    CHECK (recipient != 'news'),
    CHECK (recipient != 'webmaster'),
    CHECK (recipient != 'ftp'),
    CHECK (recipient != 'abuse'),
    CHECK (recipient != 'noc'),
    CHECK (recipient != 'security'),
    CHECK (recipient != 'monit'),
    CHECK (recipient != 'clamav'),
    CHECK (recipient != 'www-data')
);

CREATE TABLE mailredirection_recipient_users (
    id                      SERIAL          PRIMARY KEY,
    recipient               VARCHAR(255)    NOT NULL 
                                            REFERENCES users(act)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    original_recipient_id   INT             REFERENCES mailredirection_addresses(id)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE
);

CREATE TABLE mailredirection_recipient_groups (
    id                      SERIAL          PRIMARY KEY,
    recipient               VARCHAR(255)    NOT NULL 
                                            REFERENCES groups(act)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    original_recipient_id   INT             REFERENCES mailredirection_addresses(id)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE
);

-- disallow to enter the same recipient and original_recipient twice
-- CREATE UNIQUE INDEX mailredirection_recipient_users_key ON mailredirection_recipient_users (recipient, original_recipient_id);
-- CREATE UNIQUE INDEX mailredirection_recipient_groups_key ON mailredirection_recipient_groups (recipient, original_recipient_id);

-- permissions
GRANT SELECT, USAGE ON "mailredirection_recipient_users_id_seq", "mailredirection_recipient_groups_id_seq", "mailredirection_addresses_id_seq" TO exim, symfony;
GRANT SELECT ON "mailredirection_addresses", "mailredirection_recipient_users", "mailredirection_recipient_groups" TO exim;
GRANT INSERT, SELECT, UPDATE, DELETE ON "mailredirection_addresses", "mailredirection_recipient_users", "mailredirection_recipient_groups" TO symfony;