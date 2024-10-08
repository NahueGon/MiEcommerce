CREATE DATABASE IF NOT EXISTS miecommerce;
USE miecomerce;

DROP TABLE IF EXISTS user;
CREATE TABLE IF NOT EXISTS user(
    id            int NOT NULL AUTO_INCREMENT,
    email         varchar(180) NOT NULL,
    roles         json NOT NULL,
    password      varchar(255) NOT NULL,
    name          varchar(255) NOT NULL,
    lastname      varchar(255) DEFAULT NULL,
    gender        varchar(255) DEFAULT NULL,
    created_at    datetime NOT NULL,
    img_profile   varchar(255) DEFAULT NULL,

    CONSTRAINT pk_product PRIMARY KEY(id),
    UNIQUE KEY UNIQ_IDENTIFIER_EMAIL (email)
)ENGINE=InnoDb;

DROP TABLE IF EXISTS product;
CREATE TABLE IF NOT EXISTS product(
    id              int(11) auto_increment not null,
    product         varchar(255),
    description     text,
    price           float,


    CONSTRAINT pk_product PRIMARY KEY(id)
)ENGINE=InnoDb;

DROP TABLE IF EXISTS category;
CREATE TABLE IF NOT EXISTS category(
    id              int(11) auto_increment not null,
    name            varchar(255),
    description     text,
    views           int,

    CONSTRAINT pk_product PRIMARY KEY(id)
)ENGINE=InnoDb;