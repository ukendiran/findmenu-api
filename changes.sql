CREATE TABLE business_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE,
    description TEXT NULL,
    status INT DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);


CREATE TABLE business_type_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_type_id BIGINT UNSIGNED,
    field_name VARCHAR(255),
    field_label VARCHAR(255),
    field_type ENUM('text', 'number', 'date', 'boolean'),
    is_required TINYINT(1) DEFAULT 0,
    default_value TEXT NULL,
    `order` INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX (business_type_id),
    CONSTRAINT fk_business_type
        FOREIGN KEY (business_type_id)
        REFERENCES business_types(id)
        ON DELETE CASCADE
);


ALTER TABLE businesses
ADD COLUMN business_type_id BIGINT UNSIGNED NULL AFTER businessType,
ADD COLUMN custom_fields JSON NULL AFTER business_type_id,
ADD CONSTRAINT fk_businesses_business_type
    FOREIGN KEY (business_type_id)
    REFERENCES business_types(id)
    ON DELETE SET NULL;
