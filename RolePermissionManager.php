<?php

class RolePermissionManager
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Static list of permissions defined in the SQL file.
     *
     * @var array
     */
    public static $PERMISSIONS = [

        # evaluations
        'EVALUATION_CREATE'   => 'evaluation:create',
        'EVALUATION_UPDATE'   => 'evaluation:update',
        'EVALUATION_DELETE'   => 'evaluation:delete',
        'EVALUATION_VIEW'     => 'evaluation:view',

        # users
        'USER_READ'           => 'user:read',
        'USER_DELETE'         => 'user:delete',
        'USER_LIST'           => 'user:list',
        'USER_IMPERSONATE'    => 'user:impersonate',

        # evaluators
        'EVALUATOR_CREATE'   => 'evaluator:create',
        'EVALUATOR_DELETE'   => 'evaluator:delete',
        'EVALUATOR_LIST'     => 'evaluator:list',
        'EVALUATOR_MONITOR'  => 'evaluator:monitor',

        # supervisors
        'SUPERVISOR_CREATE'     => 'supervisor:create',
        'SUPERVISOR_DELETE'     => 'supervisor:delete',
        'SUPERVISOR_LIST'       => 'supervisor:list',
        'SUPERVISOR_MONITOR'    => 'supervisor:monitor',

        # roles
        'ROLE_CREATE'         => 'role:create',
        'ROLE_UPDATE'         => 'role:update',
        'ROLE_READ'           => 'role:read',
        'ROLE_DELETE'         => 'role:delete',
        'ROLE_LIST'           => 'role:list',

        # organizations
        'ORGANIZATION_CREATE' => 'organization:create',
        'ORGANIZATION_UPDATE' => 'organization:update',
        'ORGANIZATION_DELETE' => 'organization:delete',
        'ORGANIZATION_LIST'   => 'organization:list',
        'ORGANIZATION_TYPE_MANAGE' => 'organization_type:manage',

        # call for proposals
        'CALL_FOR_PROPOSAL_CREATE' => 'call_for_proposal:create',
        'CALL_FOR_PROPOSAL_UPDATE' => 'call_for_proposal:update',
        'CALL_FOR_PROPOSAL_DELETE' => 'call_for_proposal:delete',
        'CALL_FOR_PROPOSAL_LIST'   => 'call_for_proposal:list',
        
        # applications
        'APPLICATION_CREATE'       => 'application:create',
        'APPLICATION_UPDATE'       => 'application:update',
        'APPLICATION_DELETE'       => 'application:delete',
        'APPLICATION_LIST'         => 'application:list',
        'APPLICATION_REVIEW'       => 'application:review'
    ];

    /**
     * Constructor accepts a PDO connection.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieve a role by its name.
     *
     * @param string $roleName
     * @return array|false
     */
    public function getRoleByName(string $roleName)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM role WHERE name = :name");
        $stmt->execute(['name' => $roleName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a permission by its name.
     *
     * @param string $permissionName
     * @return array|false
     */
    public function getPermissionByName(string $permissionName)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permission WHERE name = :name");
        $stmt->execute(['name' => $permissionName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Assign a permission to a role.
     *
     * @param string $roleName
     * @param string $permissionName
     * @return bool
     */
    public function assignPermissionToRole(string $roleName, string $permissionName): bool
    {
        $role = $this->getRoleByName($roleName);
        $permission = $this->getPermissionByName($permissionName);

        if (!$role || !$permission) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO role_permission (role_id, permission_id) 
            VALUES (:role_id, :permission_id)
        ");

        return $stmt->execute([
            'role_id' => $role['id'],
            'permission_id' => $permission['id']
        ]);
    }

    /**
     * Retrieve all permissions assigned to a role.
     *
     * @param string $roleName
     * @return array
     */
    public function getPermissionsForRole(string $roleName): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.* FROM permission p
            INNER JOIN role_permission rp ON p.id = rp.permission_id
            INNER JOIN role r ON rp.role_id = r.id
            WHERE r.name = :roleName
        ");
        $stmt->execute(['roleName' => $roleName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param int $userId
     * @param string $permissionName
     * @return bool
     */
    public function userHasPermission(int $userId, string $permissionName): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM user_role ur
            INNER JOIN role_permission rp ON ur.role_id = rp.role_id
            INNER JOIN permission p ON rp.permission_id = p.id
            WHERE ur.user_id = :userId
              AND p.name = :permissionName
            LIMIT 1
        ");
        $stmt->execute([
            'userId' => $userId,
            'permissionName' => $permissionName
        ]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>
