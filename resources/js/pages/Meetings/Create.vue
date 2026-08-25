<template>
  <div class="create-page">

    <div class="form-card">
      <!-- ปุ่มส้มมุมขวาบนของ หัวข้อกองประชุม -->
      <div class="card-inner-head">
        <div class="inner-head-left"><h4>ສ້າງກອງປະຊຸມ / ສຳມະນາ</h4><small>ປ້ອນຂໍ້ມູນພື້ນຖານແລ້ວລະບົບຈະສ້າງ QR ໃຫ້ທັນທີ</small></div>
        <router-link to="/meetings" class="btn-orange">← ກັບຄືນລາຍການ</router-link>
      </div>

      <div class="form-body">
        <label>ຫົວຂໍ້ກອງປະຊຸມ <span class="req">*</span></label>
        <input v-model="form.title" class="inp" placeholder="ເຊັ່ນ: ປະຊຸມວາງແຜນປະຈຳເດືອນ" />

        <label>ປະເພດ <span class="req">*</span></label>
        <select v-model="form.type" class="inp">
          <option value="meeting">🗓️ ກອງປະຊຸມ (Meeting)</option>
          <option value="seminar">🎓 ສຳມະນາ (Seminar)</option>
          <option value="training">📚 ຝຶກອົບຮົມ (Training)</option>
          <option value="other">📌 ອື່ນໆ (Other)</option>
        </select>

        <label>ລາຍລະອຽດ</label>
        <textarea v-model="form.description" class="inp" rows="3" placeholder="ລາຍລະອຽດກອງປະຊຸມ..."></textarea>

        <label>ຫ້ອງປະຊຸມ / ສະຖານທີ່ <span class="req">*</span></label>
        <select v-model="form.location" class="inp">
          <option value="">-- ເລືອກຫ້ອງ --</option>
          <option>ຫ້ອງສຳມະນາ1</option><option>ຫ້ອງສຳມະນາ4</option><option>ຫ້ອງ 403</option><option>ຫ້ອງປະຊຸມໃຫຍ່ ຊັ້ນ 5</option><option>ຫ້ອງ 101</option><option>ຫ້ອງ 103</option>
        </select>

        <div class="row2">
          <div><label>ວັນເລີ່ມ <span class="req">*</span></label><input v-model="form.start_date" type="date" class="inp" :min="minDate" :max="maxDate" /></div>
          <div><label>ວັນສິ້ນສຸດ <span class="req">*</span></label><input v-model="form.end_date" type="date" class="inp" :min="form.start_date" :max="maxDate" /></div>
        </div>
        <div class="row2">
          <div><label>ເວລາເລີ່ມ <span class="req">*</span></label><input v-model="form.start_time" type="time" class="inp" /></div>
          <div><label>ເວລາສິ້ນສຸດ <span class="req">*</span></label><input v-model="form.end_time" type="time" class="inp" /></div>
        </div>

        <div v-if="apiError" class="api-err">⚠️ {{ apiError }}</div>
        <div v-if="isToday" class="info-box">ℹ️ ຕັ້ງຕົ້ນເປັນເວລາປັດຈຸບັນ {{ nowTime }} ອັດຕະໂນມັດ</div>
      </div>

      <div class="form-foot">
        <button @click="$router.push('/meetings')" class="btn-cancel">ຍົກເລີກ</button>
        <button @click="createMeeting" :disabled="loading" class="btn-save">{{ loading ? 'ກຳລັງບັນທຶກ...' : 'ບັນທຶກຂໍ້ມູນ' }}</button>
      </div>
    </div>

    <div v-if="showQR" class="modal-overlay"><div class="modal-card qr"><div class="success">✅ ສ້າງສຳເລັດ!</div><h5>{{ created.title }}</h5><p class="small">Code: <b class="blue">{{ created.meeting_code }}</b></p><div class="qr-box"><img :src="qrImg" /></div><div class="link">{{ qrUrl }}</div><button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button><div class="row2 mt"><button class="btn-gray w100" @click="openCheckin">ເປີດ Check-in</button><button class="btn-gray w100" @click="goList">ໄປໜ້າລາຍການ</button></div></div></div>
    <div v-if="showAlert" class="modal-overlay" @click.self="showAlert=false"><div class="modal-card small"><div class="confirm-icon orange">⚠️</div><div class="confirm-title">{{ alertTitle }}</div><div class="confirm-desc">{{ alertMsg }}</div><div class="confirm-actions"><button class="btn-blue" @click="showAlert=false">ຕົກລົງ</button></div></div></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/axios.js'

const router = useRouter()
const form = ref({ title:'', description:'', location:'', type:'meeting', start_date:'', end_date:'', start_time:'', end_time:'' })
const apiError = ref('')
const loading = ref(false)
const showQR = ref(false)
const created = ref({})
const qrUrl = ref('')
const qrImg = ref('')
const showAlert = ref(false)
const alertTitle = ref('')
const alertMsg = ref('')
const minDate = new Date().toISOString().slice(0,10)
const maxDate = '2036-12-31'
const now = new Date()
const nowTime = now.toLocaleTimeString('lo-LA', {hour:'2-digit', minute:'2-digit', hour12:false})
const isToday = computed(()=> form.value.start_date === minDate)
const showNiceAlert = (title, msg)=>{ alertTitle.value=title; alertMsg.value=msg; showAlert.value=true }
const setDefaultDateTime = ()=>{
  const now = new Date()
  const today = now.toISOString().slice(0,10)
  const hh = String(now.getHours()).padStart(2,'0')
  const mm = String(now.getMinutes()).padStart(2,'0')
  const nextHour = new Date(now.getTime() + 60*60*1000)
  const hh2 = String(nextHour.getHours()).padStart(2,'0')
  const mm2 = String(nextHour.getMinutes()).padStart(2,'0')
  form.value.start_date = today
  form.value.end_date = today
  form.value.start_time = `${hh}:${mm}`
  form.value.end_time = `${hh2}:${mm2}`
}
const createMeeting = async()=>{
  apiError.value=''
  if(!form.value.title){ showNiceAlert('ຂໍ້ມູນບໍ່ຄົບ', 'ກະລຸນາໃສ່ຫົວຂໍ້ກອງປະຊຸມ'); return }
  if(!form.value.location){ showNiceAlert('ຂໍ້ມູນບໍ່ຄົບ', 'ກະລຸນາເລືອກຫ້ອງປະຊຸມ'); return }
  loading.value=true
  try{
    const res = await api.post('/api/meetings', form.value)
    created.value = res.data.data || res.data
    qrUrl.value=`${window.location.origin}/checkin/${created.value.meeting_code}`
    qrImg.value=`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(qrUrl.value)}`
    showQR.value=true
  }catch(e){ const msg=e.response?.data?.message||'ເກີດຂໍ້ຜິດພາດ'; apiError.value=msg; showNiceAlert('ບັນທຶກບໍ່ໄດ້', msg) }
  finally{ loading.value=false }
}
const copyLink = ()=>{ navigator.clipboard.writeText(qrUrl.value) }
const openCheckin = ()=> window.open(qrUrl.value,'_blank')
const goList = ()=> router.push('/meetings')
onMounted(setDefaultDateTime)
</script>

<style scoped>
.create-page{max-width:720px; margin:0 auto; padding:0;}
.form-header-top{margin-bottom:16px;} .form-header-top h3{margin:0; font-size:18px; font-weight:800; color:#0f172a;} .form-header-top small{color:#64748b; font-size:12px;}
.form-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.04); overflow:hidden;}
.card-inner-head{display:flex; justify-content:space-between; align-items:center; padding:12px 20px; background:#fffaf0; border-bottom:1px solid #fde68a;}
.inner-head-left{font-size:12px; font-weight:700; color:#92400e;}
.btn-orange{background:#f97316; color:#fff; border:none; padding:7px 14px; border-radius:8px; font-size:11px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 6px rgba(249,115,22,.3);}
.btn-orange:hover{background:#ea580c;}
.form-body{padding:20px 22px;} .form-body label{font-size:12px; font-weight:600; color:#334155; display:block; margin:12px 0 5px;} .req{color:#ef4444;} .inp{width:100%; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; font-size:13px; outline:none;} .inp:focus{border-color:#2563eb;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:12px;} .api-err{background:#fee2e2; color:#991b1b; padding:10px; border-radius:8px; font-size:12px; margin-top:12px;} .info-box{background:#eff6ff; color:#1d4ed8; padding:8px 10px; border-radius:8px; font-size:11px; margin-top:10px;}
.form-foot{display:flex; justify-content:flex-end; gap:8px; padding:14px 22px; background:#fcfcfd; border-top:1px solid #f8fafc; border-radius:0 0 16px 16px;} .btn-cancel{background:#fff; border:1px solid #e2e8f0; padding:9px 18px; border-radius:10px; font-size:13px; cursor:pointer;} .btn-save{background:#2563eb; color:#fff; border:none; padding:9px 22px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:300;} .modal-card.qr{width:400px; background:#fff; border-radius:16px; padding:20px; text-align:center;} .modal-card.small{width:400px; background:#fff; border-radius:16px; padding:28px 24px; text-align:center; animation:pop .2s ease;} @keyframes pop{0%{transform:scale(.9); opacity:0}100%{transform:scale(1); opacity:1}} .success{background:#dcfce7; color:#166534; padding:8px; border-radius:10px; font-weight:700; margin-bottom:12px;} .qr-box{border:1px solid #f1f5f9; border-radius:14px; padding:14px; margin:14px 0; display:flex; justify-content:center;} .link{background:#f8fafc; padding:10px; border-radius:8px; font-size:11px; word-break:break-all; margin-bottom:10px;} .blue{color:#2563eb;} .small{font-size:12px; color:#64748b;} .w100{width:100%;} .mt{margin-top:12px;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:8px;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px; border-radius:10px; width:100%; cursor:pointer;} .btn-gray{background:#f1f5f9; border:none; padding:10px; border-radius:10px; width:100%; cursor:pointer; font-size:12px;}
.confirm-icon{width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 14px;} .confirm-icon.orange{background:#fef3c7;} .confirm-title{font-size:16px; font-weight:800; margin-bottom:6px;} .confirm-desc{font-size:12px; color:#64748b; margin-bottom:16px;} .confirm-actions{display:flex; gap:8px; justify-content:center;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px 18px; border-radius:10px; cursor:pointer;}
</style>
