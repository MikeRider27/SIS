<?php
// Los permisos del usuario se cargan en $_SESSION['permisos'] al iniciar sesión
// (ver backend/ingresar.php) desde la tabla user_permissions. Se administran
// por usuario desde /usuarios/create y /usuarios/list.
$menuPermissions = $_SESSION['permisos'] ?? [];

function menuCan($permission, $menuPermissions) {
    return in_array($permission, $menuPermissions, true);
}
?>
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <?php if (menuCan('rda_visor', $menuPermissions)): ?>
            <li class="nav-item">
                <a href="/paciente/view" class="nav-link">
                    <i class="nav-icon fas fa-eye text-secondary"></i>
                    <p>Visor RDA</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="/paciente/ips" class="nav-link">
                    <i class="nav-icon fas fa-search text-secondary"></i>
                    <p>Consulta RDA</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="/consultas/create" class="nav-link">
                    <i class="nav-icon fas fa-list text-secondary"></i>
                    <p>MHD RDA- ITI-65</p>
                </a>
            </li>
        <?php endif; ?>

        <?php if (menuCan('pacientes', $menuPermissions)): ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-user-injured text-secondary"></i>
                    <p>
                        Pacientes
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/pacientes/create" class="nav-link">
                            <i class="fas fa-user-plus nav-icon"></i>
                            <p>Crear Paciente</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/pacientes/list" class="nav-link">
                            <i class="fas fa-list-ul nav-icon"></i>
                            <p>Listar Pacientes</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (menuCan('profesionales', $menuPermissions)): ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-user-md text-secondary"></i>
                    <p>
                        Profesionales
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/profesional/create" class="nav-link">
                            <i class="fas fa-user-plus nav-icon"></i>
                            <p>Crear Profesional</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/profesional/list" class="nav-link">
                            <i class="fas fa-list-ul nav-icon"></i>
                            <p>Listar Profesionales</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (menuCan('organizaciones', $menuPermissions)): ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-hospital text-secondary"></i>
                    <p>
                        Organizaciones
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/organizacion/create" class="nav-link">
                            <i class="fas fa-plus-circle nav-icon"></i>
                            <p>Crear Organización</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/organizacion/list" class="nav-link">
                            <i class="fas fa-list-ul nav-icon"></i>
                            <p>Listar Organizaciones</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (menuCan('usuarios', $menuPermissions)): ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-users-cog text-secondary"></i>
                    <p>
                        Usuarios
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/usuarios/create" class="nav-link">
                            <i class="fas fa-user-plus nav-icon"></i>
                            <p>Crear Usuario</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/usuarios/list" class="nav-link">
                            <i class="fas fa-list-ul nav-icon"></i>
                            <p>Listar Usuarios</p>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>

    </ul>
</nav>
