# Add traktid column to movieinfo table

ALTER TABLE movieinfo ADD traktid INT(10) UNSIGNED NOT NULL DEFAULT 0;
