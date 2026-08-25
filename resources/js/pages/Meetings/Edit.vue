<template>
  <div class="edit-page">
    <div class="form-header-top"><div><h3>ແກ້ໄຂກອງປະຊຸມ</h3><small v-if="form.meeting_code">Code: {{ form.meeting_code }} | {{ form.title }}</small></div></div>

    <div v-if="loading" class="loading">⏳ ກຳລັງໂຫຼດ...</div>

    <div v-else class="form-card">
      <div class="card-inner-head">
        <div class="inner-head-left">
          <div class="title-orange">ແກ້ໄຂກອງປະຊຸມ</div>
          <small v-if="isScheduled">ເລືອກວັນ/ເວລາເລີ່ມຫ້າມໜ້ອຍກວ່າປັດຈຸບັນ</small>
          <small v-else-if="isOngoing">ແກ້ໄດ້ສະເພາະ ວັນ/ເວລາສິ້ນສຸດ</small>
        </div>
        <router-link to="/meetings" class="btn-orange">← ກັບຄືນລາຍການ</router-link>
      </div>

      <div v-if="isOngoing" class="ongoing-alert">🟢 ກອງປະຊຸມກຳລັງດຳເນີນງານ - ແກ້ໄຂໄດ້ສະເພາະ ວັນ/ເວລາສິ້ນສຸດ ເທົ່ານັ້ນ</div>

      <div class="form-body">
        <label>ຫົວຂໍ້ກອງປະຊຸມ <span class="req">*</span></label>
        <input v-model="form.title" class="inp" :disabled="isOngoing" :class="{disabled: isOngoing}" />

        <label>ປະເພດ <span class="req">*</span></label>
        <select v-model="form.type" class="inp" :disabled="isOngoing" :class="{disabled: isOngoing}">
          <option value="meeting">🗓️ ກອງປະຊຸມ</option><option value="seminar">🎓 ສຳມະນາ</option><option value="training">📚 ຝຶກອົບຮົມ</option><option value="other">📌 ອື່ນໆ</option>
        </select>

        <label>ລາຍລະອຽດ</label>
        <textarea v-model="form.description" class="inp" rows="3" :disabled="isOngoing" :class="{disabled: isOngoing}"></textarea>

        <label>ຫ້ອງປະຊຸມ / ສະຖານທີ່ <span class="req">*</span></label>
        <select v-model="form.location" class="inp" :disabled="isOngoing" :class="{disabled: isOngoing}">
          <option>ຫ້ອງສຳມະນາ1</option><option>ຫ້ອງສຳມະນາ4</option><option>ຫ້ອງ 403</option><option>ຫ້ອງປະຊຸມໃຫຍ່ ຊັ້ນ 5</option><option>ຫ້ອງ 101</option><option>ຫ້ອງ 103</option>
        </select>

        <div class="row2">
          <div>
            <label>ວັນເລີ່ມ <span class="req">*</span></label>
            <input v-model="form.start_date" type="date" class="inp" :min="minDate" :max="maxDate" :disabled="isOngoing" :class="{disabled: isOngoing}" @change="validateStartDateTime" />
            <span v-if="isScheduled && startDateError" class="err-msg">{{ startDateError }}</span>
            <span v-if="isOngoing" class="lock-hint">🔒 ລັອກ</span>
          </div>
          <div>
            <label>ວັນສິ້ນສຸດ <span class="req">*</span></label>
            <input v-model="form.end_date" type="date" class="inp" :min="form.start_date || minDate" :max="maxDate" @change="validateEndDateTime" />
          </div>
        </div>

        <div class="row2">
          <div>
            <label>ເວລາເລີ່ມ <span class="req">*</span></label>
            <input v-model="form.start_time" type="time" class="inp" :disabled="isOngoing" :class="{disabled: isOngoing}" @change="validateStartDateTime" />
            <span v-if="isScheduled && startTimeError" class="err-msg">{{ startTimeError }}</span>
          </div>
          <div>
            <label>ເວລາສິ້ນສຸດ <span class="req">*</span></label>
            <input v-model="form.end_time" type="time" class="inp" @change="validateEndDateTime" />
            <span v-if="endTimeError" class="err-msg">{{ endTimeError }}</span>
          </div>
        </div>

        <div v-if="apiError" class="api-err">⚠️ {{ apiError }}</div>
      </div>
      <div class="form-foot">
        <button @click="$router.push('/meetings')" class="btn-cancel">ຍົກເລີກ</button>
        <button @click="updateMeeting" :disabled="saving || hasValidationError" class="btn-save" :class="{disabledBtn: hasValidationError}">{{ saving ? 'ກຳລັງບັນທຶກ...' : 'ບັນທຶກການແກ້ໄຂ' }}</button>
      </div>
    </div>

    <div v-if="showSuccess" class="modal-overlay"><div class="modal-card small"><div class="confirm-icon green">✅</div><div class="confirm-title">ສຳເລັດການແກ້ໄຂຂໍ້ມູນ</div><div class="confirm-desc">ຂໍ້ມູນ "{{ form.title }}" ແກ້ໄຂສຳເລັດແລ້ວ ກຳລັງໄປໜ້າລາຍການ...</div><div class="progress-bar"><div class="progress-fill"></div></div></div></div>

    <div v-if="showAlert" class="modal-overlay" @click.self="showAlert=false"><div class="modal-card small"><div class="confirm-icon orange">⚠️</div><div class="confirm-title">{{ alertTitle }}</div><div class="confirm-desc">{{ alertMsg }}</div><div class="confirm-actions"><button class="btn-blue" @click="showAlert=false">ຕົກລົງ</button></div></div></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../utils/axios.js'

const route = useRoute()
const router = useRouter()
const id = route.params.id
const form = ref({ title:'', description:'', location:'', type:'meeting', start_date:'', end_date:'', start_time:'', end_time:'', meeting_code:'', status:'' })
const loading = ref(true)
const saving = ref(false)
const apiError = ref('')
const showSuccess = ref(false)
const showAlert = ref(false)
const alertTitle = ref('')
const alertMsg = ref('')
const startDateError = ref('')
const startTimeError = ref('')
const endTimeError = ref('')

const maxDate='2036-12-31'
const minDate = new Date().toISOString().slice(0,10)

const showNiceAlert = (t,m)=>{ alertTitle.value=t; alertMsg.value=m; showAlert.value=true }

const autoStatus = computed(()=>{
  try{
    const now=new Date()
    const s=new Date(String(form.value.start_date).slice(0,10)+'T'+String(form.value.start_time).slice(0,5))
    const e=new Date(String(form.value.end_date).slice(0,10)+'T'+String(form.value.end_time).slice(0,5))
    if(form.value.status==='completed') return 'completed'
    if(now < s) return 'scheduled'
    if(now>=s && now<=e) return 'ongoing'
    return 'completed'
  }catch{ return form.value.status }
})
const isOngoing = computed(()=> autoStatus.value==='ongoing')
const isScheduled = computed(()=> autoStatus.value==='scheduled')
const hasValidationError = computed(()=> !!(startDateError.value || startTimeError.value || endTimeError.value))

const getNowTime = ()=>{
  const n = new Date()
  return `${String(n.getHours()).padStart(2,'0')}:${String(n.getMinutes()).padStart(2,'0')}`
}

const validateStartDateTime = ()=>{
  startDateError.value=''
  startTimeError.value=''
  if(isOngoing.value) return
  if(form.value.start_date < minDate){
    startDateError.value=`ວັນເລີ່ມຫ້າມໜ້ອຍກວ່າມື້ນີ້ (${minDate})`
    return
  }
  if(form.value.start_date === minDate){
    const now = getNowTime()
    if(form.value.start_time && form.value.start_time < now){
      startTimeError.value=`ເວລາເລີ່ມຫ້າມໜ້ອຍກວ່າປັດຈຸບັນ (${now})`
    }
  }
  validateEndDateTime()
}

const validateEndDateTime = ()=>{
  endTimeError.value=''
  if(!form.value.end_date || !form.value.start_date) return
  if(form.value.end_date < form.value.start_date){
    endTimeError.value='ວັນສິ້ນສຸດຕ້ອງ ≥ ວັນເລີ່ມ'
    return
  }
  if(form.value.end_date === form.value.start_date){
    if(form.value.start_time && form.value.end_time && form.value.end_time <= form.value.start_time){
      endTimeError.value='ເວລາສິ້ນສຸດຕ້ອງ > ເວລາເລີ່ມ'
    }
  }
}

const fetchMeeting = async()=>{
  try{
    const res=await api.get(`/api/meetings/${id}`)
    const m=res.data.data||res.data
    form.value={
      meeting_code:m.meeting_code,
      title:m.title,
      description:m.description||'',
      type:m.type||'meeting',
      location:m.location,
      start_date:String(m.start_date).slice(0,10),
      end_date:String(m.end_date).slice(0,10),
      start_time:String(m.start_time).slice(0,5),
      end_time:String(m.end_time).slice(0,5),
      status:m.status
    }
    validateStartDateTime()
  }catch(e){ router.push('/meetings') }
  finally{ loading.value=false }
}

const updateMeeting = async()=>{
  validateStartDateTime()
  validateEndDateTime()
  if(hasValidationError.value){
    showNiceAlert('ເວລາບໍ່ຖືກຕ້ອງ', startDateError.value || startTimeError.value || endTimeError.value)
    return
  }
  saving.value=true
  apiError.value=''
  try{
    await api.put(`/api/meetings/${id}`, form.value)
    showSuccess.value=true
    setTimeout(()=> router.push('/meetings'),1200)
  }catch(e){
    const msg = e.response?.data?.message || 'ເກີດຂໍ້ຜິດພາດ'
    apiError.value = msg
    showNiceAlert('ບັນທຶກບໍ່ໄດ້', msg)
  }finally{ saving.value=false }
}

onMounted(fetchMeeting)
</script>

<style scoped>
.edit-page{max-width:720px; margin:0 auto; padding:0;}
.form-header-top{margin-bottom:16px;} .form-header-top h3{margin:0; font-size:18px; font-weight:800;} .form-header-top small{color:#64748b; font-size:12px;}
.loading{text-align:center; padding:40px; color:#64748b;}
.form-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.04); overflow:hidden;}
.card-inner-head{display:flex; justify-content:space-between; align-items:center; padding:14px 20px; background:#fffbeb; border-bottom:1px solid #fde68a;}
.title-orange{font-size:14px; font-weight:800; color:#92400e;} .card-inner-head small{font-size:11px; color:#b45309; display:block; margin-top:2px;}
.btn-orange{background:#f97316; color:#fff; border:none; padding:8px 14px; border-radius:8px; font-size:11px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 6px rgba(249,115,22,.3);}
.btn-orange:hover{background:#ea580c;}
.ongoing-alert{background:#d1fae5; color:#065f46; padding:10px 12px; margin:12px 20px 0; border-radius:10px; font-size:12px; font-weight:600; border:1px solid #a7f3d0;}
.form-body{padding:20px 22px;} .form-body label{font-size:12px; font-weight:600; color:#334155; display:block; margin:12px 0 5px;} .req{color:#ef4444;} .inp{width:100%; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; font-size:13px; outline:none;} .inp:focus{border-color:#2563eb;} .inp.disabled{background:#f1f5f9; opacity:.6; border-style:dashed; cursor:not-allowed;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:12px;} .api-err{background:#fee2e2; color:#991b1b; padding:10px; border-radius:8px; font-size:12px; margin-top:12px;} .lock-hint{font-size:10px; color:#ef4444; background:#fee2e2; padding:2px 6px; border-radius:10px; margin-top:4px; display:inline-block;} .edit-hint{font-size:10px; color:#065f46; background:#d1fae5; padding:2px 6px; border-radius:10px; margin-top:4px; display:inline-block;} .err-msg{font-size:11px; color:#ef4444; background:#fef2f2; padding:4px 8px; border-radius:6px; margin-top:4px; display:inline-block; font-weight:600;}
.form-foot{display:flex; justify-content:flex-end; gap:8px; padding:14px 22px; background:#fcfcfd; border-top:1px solid #f8fafc; border-radius:0 0 16px 16px;} .btn-cancel{background:#fff; border:1px solid #e2e8f0; padding:9px 18px; border-radius:10px; cursor:pointer;} .btn-save{background:#2563eb; color:#fff; border:none; padding:9px 22px; border-radius:10px; font-weight:700; cursor:pointer;} .btn-save.disabledBtn{opacity:.4; cursor:not-allowed; background:#94a3b8;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:200;} .modal-card.small{width:400px; background:#fff; border-radius:16px; padding:28px 24px; text-align:center; animation:pop .25s ease;} @keyframes pop{0%{transform:scale(.9); opacity:0}100%{transform:scale(1); opacity:1}} .confirm-icon{width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 14px;} .confirm-icon.green{background:#d1fae5;} .confirm-icon.orange{background:#fef3c7;} .confirm-title{font-size:17px; font-weight:800; margin-bottom:8px;} .confirm-desc{font-size:13px; color:#64748b; margin-bottom:16px; line-height:1.5;} .progress-bar{height:4px; background:#f1f5f9; border-radius:10px; overflow:hidden;} .progress-fill{height:100%; width:100%; background:#10b981; animation:load 1.2s ease-in-out;} @keyframes load{0%{width:0}100%{width:100%}} .confirm-actions{display:flex; gap:8px; justify-content:center;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px 18px; border-radius:10px; cursor:pointer;}
</style>
