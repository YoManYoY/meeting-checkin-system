import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '../components/Layout/AppLayout.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('../pages/Auth/Login.vue'), meta: { public: true } },
    { path: '/checkin/:token', name: 'public-checkin', component: () => import('../pages/CheckIn/Register.vue'), meta: { public: true } },
    {
      path: '/',
      component: AppLayout,
      children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue'), meta:{ title:'ພາບລວມ' } },
        { path: 'meetings', name: 'meetings', component: () => import('../pages/Meetings/Index.vue'), meta:{ title:'ຈັດການກອງປະຊຸມ' } },
        { path: 'meetings/create', name: 'meetings-create', component: () => import('../pages/Meetings/Create.vue'), meta:{ title:'ສ້າງກອງປະຊຸມ' } },
        { path: 'meetings/:id/edit', name: 'meetings-edit', component: () => import('../pages/Meetings/Edit.vue'), meta:{ title:'ແກ້ໄຂກອງປະຊຸມ' } },
        { path: 'meetings/:id', name: 'meetings-detail', component: () => import('../pages/Meetings/Detail.vue'), meta:{ title:'ລາຍລະອຽດ' } },
        { path: 'checkins', name: 'checkins', component: () => import('../pages/CheckIn/Index.vue'), meta:{ title:'Check-in ລວມ' } },
        // ✅ แบบสวยมีเมนูซ้าย - ใช้ path นี้แทน /admin/checkins
        { path: 'checkin/invited/:token', name: 'invited-checkin', component: () => import('../pages/CheckIn/OrganizerScan.vue'), meta:{ title:'ຈັດການ Check-in (ผู้จัด) 2/3' } },
        { path: 'users', name: 'users', component: () => import('../pages/Users/Index.vue'), meta:{ title:'ຜູ້ໃຊ້' } },
      ]
    },
    { path: '/:pathMatch(.*)*', redirect: '/meetings' }
  ]
})
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const isPublic = to.meta.public
  if (!isPublic && !token && to.path !== '/login') return next('/login')
  if (isPublic && token && to.path === '/login') return next('/dashboard')
  next()
})
export default router
