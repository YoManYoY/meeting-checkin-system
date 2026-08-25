<template>
  <div class="login-wrap">
    <div class="login-card">
      <h2 class="title">Meeting Check-in</h2>
      <div v-if="error" class="err">{{ error }}</div>
      <div class="form">
        <label>ອີເມວ</label>
        <input v-model="form.email" type="email" placeholder="admin@example.com" />
        <label>ລະຫັດຜ່ານ</label>
        <input v-model="form.password" type="password" placeholder="••••••••" @keyup.enter="login" />
        <button @click="login" :disabled="loading" class="btn">{{ loading ? 'ກຳລັງເຂົ້າ...' : 'ເຂົ້າສູ່ລະບົບ' }}</button>
        <small>ລອງ: admin@example.com / password</small>
      </div>
    </div>
  </div>
</template>
<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../utils/axios.js';
export default {
  setup(){
    const router = useRouter();
    const form = ref({email:'admin@example.com', password:'password'});
    const error = ref('');
    const loading = ref(false);
    const login = async () => {
      error.value=''; loading.value=true;
      try{
        // ✅ ต้องเรียก /api/auth/login เพราะ baseURL = /api
        const res = await api.post('/auth/login', {email: form.value.email, password: form.value.password});
        const token = res.data.token || res.data.access_token || res.data.data?.token;
        if(token){
          localStorage.setItem('auth_token', token);
          localStorage.setItem('token', token);
          router.push('/meetings');
        }else{
          error.value='ไม่พบ token';
        }
      }catch(e){
        console.error(e);
        error.value = e.response?.data?.message || 'The POST method is not supported - ตรวจสอบ baseURL ใน axios.js ต้องเป็น /api';
      }finally{ loading.value=false; }
    };
    return {form, error, loading, login};
  }
}
</script>
<style scoped>
.login-wrap{display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f8fafc;font-family:'Noto Sans Lao',sans-serif;}
.login-card{background:#fff;padding:28px;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.12);width:360px;}
.title{text-align:center;color:#2563eb;margin:0 0 12px;}
.err{background:#fee2e2;color:#991b1b;padding:8px 10px;border-radius:8px;font-size:13px;margin-bottom:10px;text-align:center;}
.form{display:flex;flex-direction:column;gap:8px;} input{border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;}
.btn{background:#2563eb;color:#fff;border:none;padding:11px;border-radius:10px;font-weight:700;cursor:pointer;margin-top:6px;}
small{color:#94a3b8;text-align:center;margin-top:8px;}
</style>
