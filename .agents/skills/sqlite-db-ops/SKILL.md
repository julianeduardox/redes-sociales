---
name: sqlite-db-ops
description: >-
  Especialista en operaciones de base de datos SQLite, migraciones seguras y optimización de consultas.
  Utilizar al alterar esquemas en database.sqlite, crear nuevas tablas, optimizar índices de rendimiento,
  ejecutar respaldos o verificar la integridad y aislamiento multi-tenant del sistema.
---

# 🗄️ SQLite Database Operations & Schema Migrations

Esta skill proporciona las mejores prácticas y procedimientos operativos seguros para gestionar la base de datos SQLite del proyecto ubicada en `data/database.sqlite`.

---

## 🛡️ 1. Protocolo Obligatorio Previo a Modificaciones

Antes de ejecutar cualquier cambio de esquema o migración destructiva:

1. **Crear una copia de seguridad en caliente:**
   ```powershell
   # En PowerShell:
   Copy-Item "c:\xampp\htdocs\Redes sociales\data\database.sqlite" "c:\xampp\htdocs\Redes sociales\data\database_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sqlite"
   ```
2. **Verificar que no haya bloqueos de escritura activos.**

---

## 🏗️ 2. Patrón de Migración Segura en SQLite

Dado que SQLite tiene limitaciones con `ALTER TABLE` (por ejemplo, para modificar tipos de columnas, restricciones `NOT NULL` o claves foráneas), se debe seguir el patrón de 4 pasos:

```sql
-- 1. Iniciar transacción
BEGIN TRANSACTION;

-- 2. Crear nueva tabla con la estructura deseada
CREATE TABLE brand_voices_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    brand_name TEXT NOT NULL,
    warmth_level INTEGER DEFAULT 5,
    expertise_level INTEGER DEFAULT 5,
    conversion_level INTEGER DEFAULT 5,
    custom_instructions TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Copiar datos de la tabla anterior
INSERT INTO brand_voices_new (id, client_id, brand_name, custom_instructions, created_at)
SELECT id, client_id, brand_name, custom_instructions, created_at FROM brand_voices;

-- 4. Reemplazar tabla anterior
DROP TABLE brand_voices;
ALTER TABLE brand_voices_new RENAME TO brand_voices;

-- 5. Recrear índices necesarios
CREATE INDEX IF NOT EXISTS idx_brand_voices_client ON brand_voices(client_id);

COMMIT;
```

---

## ⚡ 3. Optimización e Índices Esenciales

Para mantener alta velocidad en paneles con miles de comentarios:

* **Índices de aislamiento multi-tenant:**
  ```sql
  CREATE INDEX IF NOT EXISTS idx_comments_brand_created ON comments(brand_id, created_at DESC);
  CREATE INDEX IF NOT EXISTS idx_posts_client ON posts(client_id, published_at DESC);
  CREATE INDEX IF NOT EXISTS idx_logs_created ON system_logs(created_at DESC);
  ```

---

## 🔍 4. Comprobación de Integridad y Mantenimiento

Ejecutar periódicamente mediante PHP o CLI:

```sql
PRAGMA integrity_check;
PRAGMA foreign_keys = ON;
VACUUM;
```
