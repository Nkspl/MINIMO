-- 1. Crear la base de datos y seleccionarla
CREATE DATABASE IF NOT EXISTS minimoo_db
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;
  
USE minimoo_db;


-- 2. Usuarios (login)
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  rut           VARCHAR(20)   NOT NULL UNIQUE,
  clave         VARCHAR(255)  NOT NULL,            -- hasheada con password_hash()
  nombre        VARCHAR(100)  NOT NULL,
  apellido      VARCHAR(100)  NOT NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Grupos de artículos (códigos como en tu Excel: 10, 100, 110…)
CREATE TABLE IF NOT EXISTS grupo_articulos (
  codigo        INT          PRIMARY KEY,          -- 10, 100, 110…
  descripcion   VARCHAR(100) NOT NULL,             -- MOTOR, BANO, …
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 4. Maestro de inventario (tabla central de ítems)
CREATE TABLE IF NOT EXISTS maestro_inventario (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  parte                VARCHAR(50) NOT NULL UNIQUE,              -- “64065”, “50101”, …
  descripcion          TEXT          NOT NULL,           -- tu descripción larga
  codigo_oem           VARCHAR(255)  NULL,               -- “MP801204-70000174619-703072”
  grupo_codigo         INT           NOT NULL,           -- FK a grupo_articulos.codigo
  rotativo             ENUM('S','N') NOT NULL DEFAULT 'N',
  kit                  ENUM('S','N') NOT NULL DEFAULT 'N',
  condicion_habil      ENUM('S','N') NOT NULL DEFAULT 'N',
  estado               ENUM('ACTIVE','PENDOBS','PENDING')
                                 NOT NULL DEFAULT 'ACTIVE',
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_codigo)
    REFERENCES grupo_articulos(codigo)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 5. Inventario (stock y costos), vinculado al maestro
CREATE TABLE IF NOT EXISTS inventario (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  maestro_id           INT          NOT NULL,           -- FK a maestro_inventario.id
  bodega               VARCHAR(20)  NOT NULL,
  condicion            VARCHAR(50)  NULL,
  estante              VARCHAR(50)  NULL,
  cantidad             INT          NOT NULL DEFAULT 0,
  costo_promedio       DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  costo_ultima_compra  DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  costo_total          DECIMAL(20,2) AS (cantidad * costo_promedio) STORED,
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maestro_id)
    REFERENCES maestro_inventario(id)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 6. Videos asociados a cada ítem (máx. 2 min)
CREATE TABLE IF NOT EXISTS videos (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  maestro_id           INT          NOT NULL,
  ruta_video           VARCHAR(255) NOT NULL,           -- uploads/videos/…
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maestro_id)
    REFERENCES maestro_inventario(id)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 7. Imágenes asociadas (hasta 5 por ítem)
CREATE TABLE IF NOT EXISTS imagenes (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  maestro_id           INT          NOT NULL,
  ruta_imagen          VARCHAR(255) NOT NULL,           -- uploads/images/…
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maestro_id)
    REFERENCES maestro_inventario(id)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 8. Documentos asociados (PDF, Word, etc.)
CREATE TABLE IF NOT EXISTS documentos (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  maestro_id           INT          NOT NULL,
  ruta_documento       VARCHAR(255) NOT NULL,           -- uploads/documents/…
  tipo                 VARCHAR(10),                       -- pdf, docx, …
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (maestro_id)
    REFERENCES maestro_inventario(id)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 9) Tabla de Activos (números de bus y sus datos)
ALTER TABLE activos
  MODIFY COLUMN ano YEAR NULL;
  
CREATE TABLE IF NOT EXISTS activos (
  activo               INT           PRIMARY KEY,       -- p.ej. 6750, 6751…
  descripcion          VARCHAR(255)  NOT NULL,            -- “CAMION SAMEX”
  alias                VARCHAR(50) NULL,                        -- “CTHJ59-X”
  ano                  YEAR  NULL,                            -- 2011
  fabricante           VARCHAR(100),                       -- “MB”, “MARCOPOLO”…
  modelo               VARCHAR(100),
  descripcion_ext      TEXT   NULL,                          -- otro campo Description
  ubicacion            VARCHAR(50) NULL,                        -- “01-10”
  ubicacion_desc       VARCHAR(100) NULL,                       -- “Carga SAMEX”
  planta               VARCHAR(50) NULL,                        -- “TURBUS”
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 10) Tabla de Satisfacción de Inventario
CREATE TABLE IF NOT EXISTS satisfaccion_inventario (
  id                    INT           AUTO_INCREMENT PRIMARY KEY,
  
  -- Datos de la solicitud
  ot                    BIGINT        NOT NULL,           -- orden de trabajo
  activo_solicitado     INT           NOT NULL,           -- FK → activos.activo
  fecha_solicitud       DATETIME      NOT NULL,
  requerido_por         VARCHAR(20)   NOT NULL,           -- sin FK → users.rut
  parte_maestro         VARCHAR(50)           NOT NULL,           -- FK → maestro_inventario.id
  cantidad_solicitada   INT           NOT NULL,
  almacen_origen        VARCHAR(20),
  id_vale               VARCHAR(50),
  fecha                 DATETIME,
  transaccion           VARCHAR(20),
  asset_destino         INT           NOT NULL,           -- FK → activos.activo
  
  -- Datos de la entrega / despacho
  almacen_destino       VARCHAR(20),
  cantidad_entregada    INT,
  costo_unitario        DECIMAL(20,2),
  costo_total_entregado DECIMAL(20,2),
  despachado_por    VARCHAR(20),                         -- sin FK → users.rut
  diferencia_cant       INT,                               -- Dif.Cant.Env.y Rec.
  
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- ► Claves foráneas
  FOREIGN KEY (activo_solicitado)
    REFERENCES activos(activo)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
      
  FOREIGN KEY (asset_destino)
    REFERENCES activos(activo)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
      
  FOREIGN KEY (parte_maestro)
    REFERENCES maestro_inventario(parte)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;







-- vista para lograr ver todos los datos de inventario completo
USE minimoo_db;

CREATE OR REPLACE VIEW inventario_completo AS
SELECT
  i.id,
  i.maestro_id,
  mi.parte             AS codigo,
  mi.codigo_oem        AS oem,
  mi.descripcion,
  i.bodega,
  i.condicion,
  i.estante,
  i.cantidad,
  i.costo_promedio,
  i.costo_ultima_compra,
  i.costo_total,
  i.created_at
FROM inventario AS i
JOIN maestro_inventario AS mi
  ON i.maestro_id = mi.id;










