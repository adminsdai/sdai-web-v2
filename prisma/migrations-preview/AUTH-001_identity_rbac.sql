-- AUTH-001 v0.1 - PREVIEW ONLY
-- Do not execute directly in production.
-- Generated as an auditable migration plan before deployment.

CREATE TABLE identity_roles (
  id INT NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  UNIQUE KEY uq_identity_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE identity_permissions (
  id INT NOT NULL AUTO_INCREMENT,
  code VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_identity_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE identity_user_roles (
  user_id VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  assigned_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  assigned_by VARCHAR(255) NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_identity_user_roles_role (role_id),
  CONSTRAINT fk_identity_user_roles_user FOREIGN KEY (user_id) REFERENCES authorized_users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_identity_user_roles_role FOREIGN KEY (role_id) REFERENCES identity_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE identity_role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  KEY idx_identity_role_permissions_permission (permission_id),
  CONSTRAINT fk_identity_role_permissions_role FOREIGN KEY (role_id) REFERENCES identity_roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_identity_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES identity_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE identity_sessions (
  id VARCHAR(36) NOT NULL,
  user_id VARCHAR(255) NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  expires_at DATETIME(3) NOT NULL,
  last_seen_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  revoked_at DATETIME(3) NULL,
  ip_hash VARCHAR(64) NULL,
  user_agent VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_identity_sessions_token_hash (token_hash),
  KEY idx_identity_sessions_user_expiry (user_id, expires_at),
  CONSTRAINT fk_identity_sessions_user FOREIGN KEY (user_id) REFERENCES authorized_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE identity_audit_events (
  id VARCHAR(36) NOT NULL,
  actor_user_id VARCHAR(255) NULL,
  action VARCHAR(80) NOT NULL,
  resource VARCHAR(80) NOT NULL,
  resource_id VARCHAR(255) NULL,
  outcome VARCHAR(20) NOT NULL,
  metadata JSON NULL,
  ip_hash VARCHAR(64) NULL,
  created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_identity_audit_actor_created (actor_user_id, created_at),
  KEY idx_identity_audit_resource_created (resource, created_at),
  CONSTRAINT fk_identity_audit_actor FOREIGN KEY (actor_user_id) REFERENCES authorized_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
