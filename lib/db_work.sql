SELECT s.schema_name, t.table_name, t.table_type, s.schema_owner
FROM information_schema.schemata s
         INNER JOIN information_schema.tables t ON t.table_schema = s.schema_name
WHERE schema_owner = 'linpostgres'
ORDER BY s.schema_name, t.table_name ASC;

SELECT *
FROM podcasts.ancientfaith;
SELECT *
FROM scriptures.ocadailyreadings;
SELECT *
FROM articles.orthochristian;
SELECT *
FROM articles.orthodoxchristiantheology;

CREATE TABLE podcasts.displaypodcasts
(
    id   serial primary key,
    link varchar(255)  null,
    text varchar(1000) null
)

CREATE TABLE podcasts.displayarticles
(
    id   serial primary key,
    link varchar(255)  null,
    text varchar(1000) null
) INSERT INTO podcasts.displaypodcasts
SELECT *
FROM podcasts.ancientfaith
WHERE link LIKE '%thepath%'
   OR link LIKE '%wholecounsel%'
   OR link LIKE '%scriptures%'
   OR link LIKE '%saintoftheday%'
ORDER BY link DESC
    FETCH NEXT 15 ROWS ONLY
INSERT
INTO podcasts.displaypodcasts
SELECT *
FROM podcasts.ancientfaith
WHERE link LIKE '%saintoftheday%'
   OR link LIKE '%orthodoxylive%'

SELECT *
FROM podcasts.displaypodcasts

CREATE TABLE podcasts.savedforlater
(
    id        serial primary key,
    link      varchar(255) null,
    text      varchar(255) null,
    insert_ts timestamp    not null default (current_timestamp)
)

CREATE TABLE articles.savedforlater
(
    id        serial primary key,
    link      varchar(255) null,
    text      varchar(255) null,
    insert_ts timestamp    not null default (current_timestamp)
)

select current_timestamp;
select now();


