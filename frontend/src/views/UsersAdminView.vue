<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { api } from '@/services/api';
import { useNotificationStore } from '@/stores/notification';
import PageHeader from '@/components/common/PageHeader.vue';
import type { RoleItem, User } from '@/types';

const notify = useNotificationStore();

const users = ref<User[]>([]);
const roles = ref<RoleItem[]>([]);
const allPermissions = ref<string[]>([]);

const userDialog = ref(false);
const roleDialog = ref(false);
const saving = ref(false);
const editingUser = ref<User | null>(null);
const editingRole = ref<RoleItem | null>(null);

const userForm = reactive({
    name: '',
    email: '',
    password: '',
    role: 'viewer',
    is_active: true,
});

const roleForm = reactive({
    name: '',
    permissions: [] as string[],
});

async function loadData() {
    const [usersRes, rolesRes, permsRes] = await Promise.all([
        api.get<{ data: User[] }>('/users'),
        api.get<{ data: RoleItem[] }>('/roles'),
        api.get<{ data: string[] }>('/permissions'),
    ]);
    users.value = usersRes.data.data;
    roles.value = rolesRes.data.data;
    allPermissions.value = permsRes.data.data;
}

function openCreateUser() {
    editingUser.value = null;
    Object.assign(userForm, { name: '', email: '', password: '', role: 'viewer', is_active: true });
    userDialog.value = true;
}

function openEditUser(user: User) {
    editingUser.value = user;
    Object.assign(userForm, {
        name: user.name,
        email: user.email,
        password: '',
        role: user.roles[0] || 'viewer',
        is_active: user.is_active,
    });
    userDialog.value = true;
}

async function saveUser() {
    saving.value = true;
    try {
        if (editingUser.value) {
            await api.put(`/users/${editingUser.value.id}`, userForm);
            notify.show('Usuario actualizado correctamente.', 'success');
        } else {
            await api.post('/users', userForm);
            notify.show('Usuario creado correctamente.', 'success');
        }
        userDialog.value = false;
        await loadData();
    } finally {
        saving.value = false;
    }
}

async function toggleStatus(user: User) {
    await api.patch(`/users/${user.id}/toggle-status`);
    notify.show(`Estado de ${user.name} actualizado.`, 'info');
    await loadData();
}

function openEditRole(role: RoleItem) {
    editingRole.value = role;
    roleForm.name = role.name;
    roleForm.permissions = [...role.permissions];
    roleDialog.value = true;
}

async function saveRole() {
    if (!editingRole.value) return;
    saving.value = true;
    try {
        await api.put(`/roles/${editingRole.value.id}`, roleForm);
        notify.show('Permisos del rol actualizados.', 'success');
        roleDialog.value = false;
        await loadData();
    } finally {
        saving.value = false;
    }
}

onMounted(loadData);
</script>

<template>
    <div>
        <PageHeader title="Usuarios, Roles y Permisos" subtitle="Administra el acceso y privilegios de la plataforma.">
            <template #actions>
                <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreateUser">
                    Nuevo Usuario
                </v-btn>
            </template>
        </PageHeader>

        <v-card class="mb-6">
            <v-table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id">
                        <td class="font-weight-medium">{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td>
                            <v-chip size="small" :color="u.roles.includes('admin') ? 'primary' : 'secondary'">
                                {{ u.roles.join(', ') }}
                            </v-chip>
                        </td>
                        <td>
                            <v-switch :model-value="u.is_active" color="success" hide-details density="compact"
                                @update:model-value="toggleStatus(u)" />
                        </td>
                        <td class="text-right">
                            <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEditUser(u)" />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-card>
            <v-card-title>Roles y Permisos Asignados</v-card-title>
            <v-card-text>
                <v-row>
                    <v-col v-for="r in roles" :key="r.id" cols="12" md="6">
                        <v-sheet border rounded class="pa-4">
                            <div class="d-flex justify-space-between align-center mb-2">
                                <span class="text-subtitle-1 font-weight-bold text-uppercase">{{ r.name }}</span>
                                <v-btn size="x-small" variant="tonal" prepend-icon="mdi-pencil"
                                    @click="openEditRole(r)">
                                    Editar permisos
                                </v-btn>
                            </div>
                            <div class="d-flex flex-wrap ga-1">
                                <v-chip v-for="perm in r.permissions" :key="perm" size="x-small" variant="tonal">
                                    {{ perm }}
                                </v-chip>
                            </div>
                        </v-sheet>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <!-- Modal Usuario -->
        <v-dialog v-model="userDialog" max-width="480">
            <v-card>
                <v-card-title>{{ editingUser ? 'Editar Usuario' : 'Crear Usuario' }}</v-card-title>
                <v-card-text>
                    <v-text-field v-model="userForm.name" label="Nombre" variant="outlined" class="mb-2" />
                    <v-text-field v-model="userForm.email" label="Correo" type="email" variant="outlined"
                        class="mb-2" />
                    <v-text-field v-model="userForm.password"
                        :label="editingUser ? 'Nueva contraseña (opcional)' : 'Contraseña'" type="password"
                        variant="outlined" class="mb-2" />
                    <v-select v-model="userForm.role" :items="roles.map((r) => r.name)" label="Rol"
                        variant="outlined" />
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="userDialog = false">Cancelar</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveUser">Guardar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal Permisos de Rol -->
        <v-dialog v-model="roleDialog" max-width="480">
            <v-card>
                <v-card-title>Permisos del rol: {{ roleForm.name }}</v-card-title>
                <v-card-text>
                    <v-checkbox v-for="perm in allPermissions" :key="perm" v-model="roleForm.permissions" :label="perm"
                        :value="perm" density="compact" hide-details />
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer />
                    <v-btn variant="text" @click="roleDialog = false">Cancelar</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveRole">Actualizar Permisos</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>