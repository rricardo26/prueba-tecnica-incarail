import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { public: true },
    },
    {
      path: '/',
      component: () => import('@/components/common/AppLayout.vue'),
      children: [
        { path: '', redirect: '/competitions' },
        {
          path: 'competitions',
          name: 'competitions',
          component: () => import('@/views/CompetitionsView.vue'),
          meta: { permission: 'competitions.read' },
        },
        {
          path: 'teams',
          name: 'teams',
          component: () => import('@/views/TeamsView.vue'),
          meta: { permission: 'teams.read' },
        },
        {
          path: 'teams/:id',
          name: 'team-detail',
          component: () => import('@/views/TeamDetailView.vue'),
          meta: { permission: 'teams.read' },
        },
        {
          path: 'players',
          name: 'players',
          component: () => import('@/views/PlayersView.vue'),
          meta: { permission: 'players.read' },
        },
        {
          path: 'admin/users',
          name: 'admin-users',
          component: () => import('@/views/UsersAdminView.vue'),
          meta: { permission: 'users.manage' },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/competitions',
    },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  const notify = useNotificationStore();

  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login' };
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'competitions' };
  }

  const requiredPermission = to.meta.permission as string | undefined;
  if (requiredPermission && !auth.can(requiredPermission)) {
    notify.show('No tienes permisos para acceder a esta pantalla.', 'error');
    return { name: 'competitions' };
  }
});

export default router;