<template>
  <div class="meetings-page">
    <div class="filter-head">
      <div class="filter-bar">
        <div class="search-box"><span>🔍</span><input v-model="searchText" placeholder="ຄົ້ນຫາ ຫົວຂໍ້, ຫ້ອງ, ປະເພດ..." class="search-inp" /></div>
        <div class="date-filter"><span class="date-label">📅 ວັນທີ:</span><input v-model="dateFrom" type="date" class="date-inp" /><button v-if="dateFrom||dateTo" @click="dateFrom=''; dateTo=''" class="btn-clear">✕ ລ້າງ</button></div>
      </div>
      <router-link to="/meetings/create" class="btn-blue">+ ສ້າງກອງປະຊຸມ</router-link>
    </div>

    <div class="pagination-bar top">
      <div class="page-left"><span class="page-info">ສະແດງ   </span><select :value="perPage" @change="changePerPage(parseInt($event.target.value))" class="page-select"><option :value="5">5</option><option :value="10">10</option><option :value="20">20</option></select><span class="page-info">   ລາຍການຕໍ່ໜ້າ • {{ paginationInfo.start }}-{{ paginationInfo.end }} ຈາກ {{ paginationInfo.total }} ລາຍການ</span></div>
      <div class="page-right"><span class="page-info">{{ totalPages }} ໜ້າ</span></div>
    </div>

    <div class="card-list">
      <div class="empty" v-if="filteredMeetings.length==0 && meetings.length==0">ຍັງບໍ່ມີກອງປະຊຸມ</div>
      <div class="empty" v-else-if="filteredMeetings.length==0">🔍 ບໍ່ພົບ</div>
      <div v-for="m in paginatedMeetings" :key="m.id" class="meet-item" :class="m.auto_status">
        <div class="meet-left">
          <div class="row-top">
            <span class="badge" :class="m.type">{{ typeLabel(m.type) }}</span>
            <b class="title-text">{{ m.title }}</b>
            <span class="loc-badge">📍 {{ m.location }}</span>
            <span class="status-badge" :class="m.auto_status"><span v-if="m.auto_status==='scheduled'">⏳ ລໍຖ້າເລີ່ມ</span><span v-else-if="m.auto_status==='ongoing'">🟢 ດຳເນີນງານ</span><span v-else-if="m.auto_status==='completed'">✅ ສຳເລັດແລ້ວ</span><span v-else>{{ m.auto_status }}</span></span>
          </div>
          <div class="meta">📅 {{ formatDate(m.start_date) }} {{ formatTime(m.start_time) }} - {{ formatDate(m.end_date) }} {{ formatTime(m.end_time) }} • 👤 {{ m.creator?.name || 'admin' }}</div>
        </div>
        <div class="meet-right">
          <button v-if="m.auto_status==='ongoing'" @click="openCompleteModal(m)" class="btn-action complete-btn">✅ ສຳເລັດ</button>
          <router-link :to="`/meetings/${m.id}`" class="btn-action detail-btn">👁️ ລາຍລະອຽດ</router-link>
          <button @click="openInvitedModal(m)" class="btn-action invited" :class="{disabled: m.auto_status==='ongoing' || m.auto_status==='completed'}" :disabled="m.auto_status==='ongoing' || m.auto_status==='completed'">👥 ຜູ້ຖືກເຊີນ <span class="count-badge">{{ m.registrations_count ?? 0 }}</span></button>
          <button @click="viewQR(m)" class="btn-action qr-blue">📷 QR</button>
          <router-link v-if="m.auto_status!=='completed'" :to="`/meetings/${m.id}/edit`" class="btn-action edit-btn">✏️ ແກ້ໄຂ</router-link>
          <span v-else class="btn-action edit-btn disabled">✏️ ແກ້ໄຂ</span>
          <button @click="openDeleteModal(m)" class="btn-action del-btn">🗑️ ລົບ</button>
        </div>
      </div>
    </div>

    <div class="pagination-bar bottom">
      <div class="page-left"><span class="page-info">ໜ້າ {{ currentPage }} ຈາກ {{ totalPages }} • ທັງໝົດ {{ paginationInfo.total }} ລາຍການ</span></div>
      <div class="page-right"><button @click="goPage(currentPage-1)" :disabled="currentPage<=1" class="page-btn">« ກ່ອນໜ້າ</button><span class="page-numbers"><button v-for="p in totalPages" :key="p" @click="goPage(p)" class="page-num" :class="{active: p===currentPage}">{{ p }}</button></span><button @click="goPage(currentPage+1)" :disabled="currentPage>=totalPages" class="page-btn">ຕໍ່ໄປ »</button></div>
    </div>

    <!-- QR -->
    <div v-if="showQR" class="modal-overlay" @click.self="showQR=false"><div class="modal-card qr"><div class="success">✅ QR Code - {{ selected?.title }}</div><div class="qr-box"><img :src="qrImg" /></div><div class="link">{{ qrUrl }}</div><button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button><div class="row2 mt"><button class="btn-gray w100" @click="showQR=false">ປິດ</button><button class="btn-gray w100" @click="openCheckin">ເປີດ Check-in</button></div></div></div>

    <!-- Invited -->
    <div v-if="showInvited" class="modal-overlay" @click.self="showInvited=false">
      <div class="modal-card large">
        <div class="modal-head blue"><div class="head-title-wrap"><h4>👥 ຜູ້ຖືກເຊີນ</h4><small>Meeting: {{ selected?.title }} | {{ selected?.meeting_code }}</small></div><button class="x white" @click="showInvited=false">✕</button></div>
        <div class="modal-body">
          <div class="form-box">
            <div class="form-title">+ ເພີ່ມຜູ້ຖືກເຊີນໃໝ່</div>
            <div class="row2"><div><label>ຊື່ <span class="req">*</span></label><input v-model="inviteForm.name" class="inp" /></div><div><label>ນາມສະກຸນ <span class="req">*</span></label><input v-model="inviteForm.lastname" class="inp" /></div></div>
            <div class="row2"><div><label>ພາກສ່ວນ / ອົງກອນ <span class="req">*</span></label><input v-model="inviteForm.organization" class="inp" /></div><div><label>ຕຳແໜ່ງ <span class="req">*</span></label><input v-model="inviteForm.position" class="inp" /></div></div>
            <label>ເບີໂທ <span class="req">*</span></label><div class="phone-group" :class="{error: phoneError}"><span class="phone-prefix">020</span><input v-model="inviteForm.phone_raw" class="inp phone-inp" maxlength="8" inputmode="numeric" pattern="[0-9]*" placeholder="5xxxxxxx" @input="validatePhone" @keypress="isNumber($event)" /></div><span v-if="phoneError" class="err-msg">{{ phoneError }}</span><span v-else class="hint-msg">💡 ກົດໄດ້ສະເພາະໂຕເລກ 8 ຫຼັກ, ຕ້ອງເລີ່ມດ້ວຍ 2, 5, 7, 8, 9 ເທົ່ານັ້ນ (ຕົວຢ່າງ: 020 5xxxxxxx)</span>
            <button class="btn-green w100 mt" @click="saveInvite" :disabled="inviteLoading">+ {{ inviteLoading ? 'ກຳລັງບັນທຶກ...' : 'ບັນທຶກລາຍຊື່' }}</button>
          </div>
          <div class="list-head">📋 ລາຍຊື່ຜູ້ຖືກເຊີນທັງໝົດ ({{ registrations.length }} ຄົນ)</div>
          <div class="table-wrap"><table class="tbl"><thead><tr><th>Reg ID</th><th>ຊື່-ນາມສະກຸນ</th><th>ພາກສ່ວນ</th><th>ຕຳແໜ່ງ</th><th>ເບີໂທ</th><th>ຈັດການ</th></tr></thead><tbody><tr v-if="registrations.length===0"><td colspan="6" class="empty-row">ບໍ່ມີລາຍການ</td></tr><tr v-for="r in registrations" :key="r.id"><td><span class="code-small">{{ r.id }}</span></td><td class="fw-bold">{{ r.name }} {{ r.lastname }}</td><td>{{ r.organization }}</td><td>{{ r.position }}</td><td>{{ r.phone }}</td><td><button @click="editInvite(r)" class="icon-btn edit">✏️</button><button @click="confirmDeleteInvite(r)" class="icon-btn del">🗑️</button></td></tr></tbody></table></div>
        </div>
        <div class="modal-foot"><button class="btn-gray" @click="showInvited=false">ປິດໜ້ານີ້</button></div>
      </div>
    </div>

    <!-- Alert สวยๆ -->
    <div v-if="showAlert" class="modal-overlay" @click.self="showAlert=false"><div class="modal-card small"><div class="confirm-icon orange">⚠️</div><div class="confirm-title">{{ alertTitle }}</div><div class="confirm-desc">{{ alertMsg }}</div><div class="confirm-actions"><button class="btn-blue" @click="showAlert=false">ຕົກລົງ</button></div></div></div>
    <div v-if="showConfirmDeleteInvite" class="modal-overlay" @click.self="showConfirmDeleteInvite=false"><div class="modal-card small"><div class="confirm-icon red">🗑️</div><div class="confirm-title">ລົບຜູ້ຖືກເຊີນ?</div><div class="confirm-desc">ລົບ "{{ deleteTarget?.name }} {{ deleteTarget?.lastname }}" ບໍ?</div><div class="confirm-actions"><button class="btn-cancel" @click="showConfirmDeleteInvite=false">ຍົກເລີກ</button><button class="btn-save red" @click="doDeleteInvite">ລົບ</button></div></div></div>

    <!-- ยืนยันสำเร็จ -->
    <div v-if="showComplete" class="modal-overlay" @click.self="showComplete=false">
      <div class="modal-card small"><div class="confirm-icon green">✅</div><div class="confirm-title">ຢືນຢັນສຳເລັດ?</div><div class="confirm-desc">ປິດກອງປະຊຸມ "{{ selected?.title }}" ກ່ອນເວລາ ເພື່ອໃຫ້ຫ້ອງວ່າງ?</div><div class="confirm-actions"><button class="btn-cancel" @click="showComplete=false">ຍົກເລີກ</button><button class="btn-save green" @click="doComplete">ສຳເລັດ</button></div></div>
    </div>
    <!-- แจ้ง สำเร็จกองประชุมแล้ว -->
    <div v-if="showCompleteSuccess" class="modal-overlay">
      <div class="modal-card small"><div class="confirm-icon green">🎉</div><div class="confirm-title">ສຳເລັດກອງປະຊຸມແລ້ວ</div><div class="confirm-desc">ກອງປະຊຸມ "{{ selected?.title }}" ສຳເລັດແລ້ວ<br>ຫ້ອງ {{ selected?.location }} ວ່າງພ້ອມໃຫ້ຜູ້ອື່ນໃຊ້ໄດ້ແລ້ວ</div><div class="confirm-actions"><button class="btn-blue" @click="showCompleteSuccess=false">ຕົກລົງ</button></div></div>
    </div>

    <!-- ยืนยันลบ -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete=false"><div class="modal-card small"><div class="confirm-icon red">🗑️</div><div class="confirm-title">ລົບຂໍ້ມູນ?</div><div class="confirm-desc">ລົບ "{{ selected?.title }}" ຖາວອນ?</div><div class="confirm-actions"><button class="btn-cancel" @click="showDelete=false">ຍົກເລີກ</button><button class="btn-save red" @click="doDelete">ລົບ</button></div></div></div>
    <!-- แจ้ง ลบข้อมูลสำเร็จแล้ว -->
    <div v-if="showDeleteSuccess" class="modal-overlay">
      <div class="modal-card small"><div class="confirm-icon red">✅</div><div class="confirm-title">ລົບຂໍ້ມູນສຳເລັດແລ້ວ</div><div class="confirm-desc">ກອງປະຊຸມ "{{ deletedTitle }}" ຖືກລົບອອກຈາກລະບົບແລ້ວ</div><div class="confirm-actions"><button class="btn-blue" @click="showDeleteSuccess=false">ຕົກລົງ</button></div></div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../utils/axios.js'

const meetings = ref([])
const registrations = ref([])
const searchText = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const showQR = ref(false)
const showComplete = ref(false)
const showCompleteSuccess = ref(false)
const showDelete = ref(false)
const showDeleteSuccess = ref(false)
const deletedTitle = ref('')
const showInvited = ref(false)
const showAlert = ref(false)
const showConfirmDeleteInvite = ref(false)
const alertTitle = ref('')
const alertMsg = ref('')
const deleteTarget = ref(null)
const selected = ref(null)
const qrUrl = ref('')
const qrImg = ref('')
const inviteLoading = ref(false)
const phoneError = ref('')
const inviteForm = ref({ name:'', lastname:'', organization:'', position:'', phone_raw:'', phone:'' })

const typeLabel = t=>({meeting:'ກອງປະຊຸມ', seminar:'ສຳມະນາ', training:'ຝຶກອົບຮົມ', other:'ອື່ນໆ'}[t]||t)
const formatDate = d=>{ if(!d) return '-'; const s=String(d).slice(0,10); const [y,m,day]=s.split('-'); return `${day}/${m}/${y}` }
const formatTime = t=> String(t||'').slice(0,5)
const showNiceAlert = (title, msg)=>{ alertTitle.value=title; alertMsg.value=msg; showAlert.value=true }

const fetchMeetings = async()=>{
  try{
    const res = await api.get('/api/meetings')
    const data = res.data.data || res.data || []
    meetings.value = (Array.isArray(data)? data : []).map(m=>{
      try{
        const now = new Date()
        const s = new Date(m.start_date.slice(0,10)+'T'+String(m.start_time).slice(0,5))
        const e = new Date(m.end_date.slice(0,10)+'T'+String(m.end_time).slice(0,5))
        let auto = m.status
        if(m.status==='completed') auto='completed'
        else if(now < s) auto='scheduled'
        else if(now>=s && now<=e) auto='ongoing'
        else auto='completed'
        return {...m, auto_status:auto}
      }catch{ return {...m, auto_status:m.status} }
    })
  }catch(e){ console.error(e) }
}
const validatePhone = ()=>{
  let raw = inviteForm.value.phone_raw.replace(/\D/g,'')
  if(raw.length>8) raw=raw.slice(0,8)
  // ກວດໂຕເລກເລີ່ມຕົ້ນ ຕ້ອງເປັນ 2,5,7,8,9 ເທົ່ານັ້ນ
  if(raw.length>0 && !['2','5','7','8','9'].includes(raw[0])){
    phoneError.value = 'ເບີໂທຕ້ອງເລີ່ມດ້ວຍ 2, 5, 7, 8, 9 ເທົ່ານັ້ນ'
    // ຕັດຕົວເລກທີ່ບໍ່ຖືກຕ້ອງອອກ ຖ້າເລີ່ມຜິດ
    if(raw.length===1){ raw=''; }
    else { raw=raw; } // ຍັງເກັບໄວ້ແຕ່ສະແດງ error
  } else if(raw.length>0 && raw.length!==8){
    phoneError.value = 'ຕ້ອງໃສ່ໃຫ້ຄົບ 8 ໂຕເລກ'
  } else {
    phoneError.value = ''
  }
  inviteForm.value.phone_raw=raw
  inviteForm.value.phone = raw ? '020'+raw : ''
}
const isNumber = (evt)=>{ const c = evt.which? evt.which : evt.keyCode; if(c>31 && (c<48 || c>57)){ evt.preventDefault(); return false } return true }
const fetchRegistrations = async(id)=>{ try{ const res = await api.get(`/api/meetings/${id}/registrations`); registrations.value = res.data.data || res.data || [] }catch{ registrations.value=[] } }
const openInvitedModal = async(m)=>{
  if(m.auto_status==='ongoing' || m.auto_status==='completed'){ showNiceAlert('ບໍ່ສາມາດເພີ່ມໄດ້', `ກອງປະຊຸມ ${m.auto_status==='ongoing' ? 'ດຳເນີນງານ' : 'ສຳເລັດແລ້ວ'} ບໍ່ສາມາດເພີ່ມຜູ້ຖືກເຊີນໄດ້`); return }
  selected.value=m; showInvited.value=true; inviteForm.value={ name:'', lastname:'', organization:'', position:'', phone_raw:'', phone:'' }; registrations.value=[]; await fetchRegistrations(m.id)
}
const saveInvite = async()=>{
  if(!inviteForm.value.name){ showNiceAlert('ໃສ່ຂໍ້ມູນບໍ່ຄົບ', 'ກະລຸນາໃສ່ ຊື່'); return }
  if(!inviteForm.value.lastname){ showNiceAlert('ໃສ່ຂໍ້ມູນບໍ່ຄົບ', 'ກະລຸນາໃສ່ ນາມສະກຸນ'); return }
  if(!inviteForm.value.organization || !inviteForm.value.position){ showNiceAlert('ໃສ່ຂໍ້ມູນບໍ່ຄົບ', 'ກະລຸນາໃສ່ ພາກສ່ວນ ແລະ ຕຳແໜ່ງ'); return }
  if(!['2','5','7','8','9'].includes(inviteForm.value.phone_raw[0])){ showNiceAlert('ເບີໂທບໍ່ຖືກ', 'ເບີໂທຕ້ອງເລີ່ມດ້ວຍ 2, 5, 7, 8, 9 ເທົ່ານັ້ນ (ຕົວຢ່າງ: 020 5xxxxxxx)'); return }
  if(inviteForm.value.phone_raw.length!==8){ showNiceAlert('ເບີໂທບໍ່ຖືກ', 'ຕ້ອງໃສ່ໃຫ້ຄົບ 8 ໂຕເລກ ຫຼັງ 020'); return }
  inviteLoading.value=true
  try{
    await api.post(`/api/meetings/${selected.value.id}/registrations`, { name: inviteForm.value.name, lastname: inviteForm.value.lastname, organization: inviteForm.value.organization, position: inviteForm.value.position, phone: inviteForm.value.phone, registration_type:'invited' })
    inviteForm.value={ name:'', lastname:'', organization:'', position:'', phone_raw:'', phone:'' }
    await fetchRegistrations(selected.value.id); fetchMeetings()
  }catch(e){ showNiceAlert('ຜິດພາດ', e.response?.data?.message||'ບັນທຶກບໍ່ໄດ້') }
  finally{ inviteLoading.value=false }
}
const confirmDeleteInvite = (r)=>{ deleteTarget.value=r; showConfirmDeleteInvite.value=true }
const doDeleteInvite = async()=>{ try{ await api.delete(`/api/meetings/${selected.value.id}/registrations/${deleteTarget.value.id}`); showConfirmDeleteInvite.value=false; await fetchRegistrations(selected.value.id); fetchMeetings() }catch{ showNiceAlert('ຜິດພາດ', 'ລົບບໍ່ໄດ້') } }
const editInvite = (r)=>{ inviteForm.value = { name:r.name, lastname:r.lastname, organization:r.organization, position:r.position, phone_raw: String(r.phone||'').replace(/^020/,''), phone:r.phone } }
const filteredMeetings = computed(()=>{ let list = meetings.value; if(searchText.value){ const kw=searchText.value.toLowerCase(); list=list.filter(m=> (m.title+m.location+m.type).toLowerCase().includes(kw)) } if(dateFrom.value) list=list.filter(m=> String(m.start_date).slice(0,10)>=dateFrom.value); if(dateTo.value) list=list.filter(m=> String(m.start_date).slice(0,10)<=dateTo.value); return list })
const totalPages = computed(()=> Math.max(1, Math.ceil(filteredMeetings.value.length / perPage.value)))
const paginationInfo = computed(()=>{ const total=filteredMeetings.value.length; const start=(currentPage.value-1)*perPage.value+1; const end=Math.min(currentPage.value*perPage.value, total); return {start: total?start:0, end, total} })
const paginatedMeetings = computed(()=>{ const start=(currentPage.value-1)*perPage.value; return filteredMeetings.value.slice(start, start+perPage.value) })
const changePerPage = v=>{ perPage.value=v; currentPage.value=1 }
const goPage = p=>{ if(p>=1 && p<=totalPages.value) currentPage.value=p }
const viewQR = m=>{ selected.value=m; qrUrl.value=`${window.location.origin}/checkin/${m.meeting_code}`; qrImg.value=`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(qrUrl.value)}`; showQR.value=true }
const copyLink = ()=>{ navigator.clipboard.writeText(qrUrl.value) }
const openCheckin = ()=> window.open(qrUrl.value,'_blank')
const openCompleteModal = m=>{ selected.value=m; showComplete.value=true }
const openDeleteModal = m=>{ selected.value=m; showDelete.value=true }

// สำเร็จ - มี Modal แจ้ง สำเร็จกองประชุมแล้ว
const doComplete = async()=>{
  try{
    await api.post(`/api/meetings/${selected.value.id}/complete`)
    showComplete.value=false
    showCompleteSuccess.value=true
    fetchMeetings()
  }catch(e){ showComplete.value=false; showNiceAlert('ຜິດພາດ', e.response?.data?.message||'error') }
}

// ลบ - มี Modal แจ้ง ลบข้อมูลสำเร็จแล้ว
const doDelete = async()=>{
  try{
    deletedTitle.value = selected.value.title
    await api.delete(`/api/meetings/${selected.value.id}`)
    showDelete.value=false
    showDeleteSuccess.value=true
    fetchMeetings()
  }catch(e){ showDelete.value=false; showNiceAlert('ຜິດພາດ','ລົບບໍ່ໄດ້') }
}

onMounted(fetchMeetings)
</script>

<style scoped>
.filter-head{display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap;} .filter-bar{display:flex; gap:8px; align-items:center;} .search-box{display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:10px; padding:7px 12px; background:#fff; gap:6px;} .search-inp{border:none; outline:none; font-size:12px; width:200px;} .date-filter{display:flex; align-items:center; gap:6px; background:#fff; border:1px solid #e2e8f0; padding:7px 10px; border-radius:10px;} .date-label{font-size:11px; color:#64748b;} .date-inp{border:none; font-size:12px; outline:none;} .btn-clear{border:none; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:8px; font-size:11px; cursor:pointer;}
.btn-blue{background:#2563eb; color:#fff; padding:8px 16px; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px; border:none;}
.card-list{display:flex; flex-direction:column; gap:10px; margin-top:12px;} .meet-item{display:flex; justify-content:space-between; gap:12px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:14px 16px; border-left:4px solid #3b82f6;} .meet-item.ongoing{border-left-color:#10b981;} .meet-item.completed{border-left-color:#94a3b8; opacity:.8;}
.row-top{display:flex; align-items:center; gap:8px; flex-wrap:wrap;} .title-text{font-size:13px; font-weight:800; color:#0f172a;} .badge{padding:3px 8px; border-radius:20px; font-size:10px; font-weight:600; border:1px solid #e2e8f0;} .badge.meeting{background:#dbeafe; color:#1d4ed8;} .badge.seminar{background:#fef9c3; color:#854d0e;} .badge.training{background:#dcfce7; color:#166534;} .loc-badge{background:#fef3c7; padding:2px 8px; border-radius:12px; font-size:11px;} .status-badge{padding:2px 8px; border-radius:12px; font-size:10px; font-weight:700;} .status-badge.scheduled{background:#fef3c7; color:#92400e;} .status-badge.ongoing{background:#dcfce7; color:#065f46;} .status-badge.completed{background:#f1f5f9; color:#475569;}
.meta{font-size:11px; color:#64748b; margin-top:6px;} .meet-right{display:flex; gap:6px; align-items:center; flex-wrap:wrap; justify-content:flex-end;} .btn-action{height:32px; padding:0 10px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; font-size:11px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px; text-decoration:none;} .btn-action.detail-btn{background:#eff6ff; color:#2563eb; border-color:#bfdbfe; font-weight:700;} .btn-action.invited{background:#dcfce7; color:#166534; border-color:#bbf7d0;} .count-badge{background:#065f46; color:#fff; font-size:11px; padding:2px 7px; border-radius:12px; margin-left:4px; font-weight:800;} .btn-action.invited.disabled{opacity:.35; cursor:not-allowed; background:#f1f5f9; color:#94a3b8;} .btn-action.qr-blue{background:#eff6ff; color:#2563eb;} .btn-action.edit-btn{background:#fef9c3; color:#854d0e;} .btn-action.del-btn{background:#fee2e2; color:#991b1b;} .btn-action.complete-btn{background:#10b981; color:#fff;} .btn-action.disabled{opacity:.35; pointer-events:none;} .empty{text-align:center; padding:40px; color:#94a3b8; background:#fff; border-radius:14px; border:1px dashed #e2e8f0;}
.pagination-bar{display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:10px 14px; margin:12px 0; flex-wrap:wrap; gap:10px;} .page-info{font-size:12px; color:#64748b;} .page-select{border:1px solid #e2e8f0; border-radius:8px; padding:4px 8px; font-size:12px;} .page-btn{border:1px solid #e2e8f0; background:#fff; padding:6px 10px; border-radius:8px; font-size:11px; cursor:pointer;} .page-btn:disabled{opacity:.4;} .page-num{width:28px; height:28px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; font-size:11px; cursor:pointer;} .page-num.active{background:#2563eb; color:#fff; border-color:#2563eb;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:300;} .modal-card{background:#fff; width:520px; max-width:95%; border-radius:18px; max-height:90vh; overflow:auto; animation:pop .2s ease;} .modal-card.large{width:720px;} .modal-card.small{width:400px; text-align:center; padding:28px 24px;} .modal-card.qr{width:400px; padding:20px; text-align:center;} @keyframes pop{0%{transform:scale(.9); opacity:0}100%{transform:scale(1); opacity:1}} .modal-head{display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #f1f5f9;} .modal-head.blue{background:#2563eb; color:#fff; border-radius:18px 18px 0 0;} .head-title-wrap h4{margin:0; font-size:14px;} .head-title-wrap small{font-size:11px; opacity:.8;} .x{background:none; border:none; font-size:18px; cursor:pointer;} .x.white{color:#fff;} .modal-body{padding:16px 20px;} .modal-body label{font-size:12px; font-weight:600; color:#334155; display:block; margin:10px 0 5px;} .inp{width:100%; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; font-size:13px;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:12px;} .modal-foot{display:flex; justify-content:flex-end; gap:8px; padding:14px 20px; background:#fafbfc; border-radius:0 0 18px 18px;} .btn-cancel{background:#fff; border:1px solid #e2e8f0; padding:10px 18px; border-radius:10px; cursor:pointer;} .btn-save{color:#fff; border:none; padding:10px 18px; border-radius:10px; cursor:pointer;} .btn-save.green{background:#10b981;} .btn-save.red{background:#ef4444;} .btn-green{background:#10b981; color:#fff; border:none; padding:11px; border-radius:10px; font-weight:700; cursor:pointer;} .btn-gray{background:#f1f5f9; border:none; padding:10px 18px; border-radius:10px; cursor:pointer;} .w100{width:100%;} .mt{margin-top:12px;} .success{background:#dcfce7; color:#166534; padding:8px; border-radius:10px; font-weight:700; margin-bottom:12px;} .qr-box{border:1px solid #f1f5f9; border-radius:14px; padding:14px; margin:14px 0; display:flex; justify-content:center;} .link{background:#f8fafc; padding:10px; border-radius:8px; font-size:11px; word-break:break-all; margin-bottom:10px;}
.form-box{background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;} .form-title{font-size:13px; font-weight:700; margin-bottom:12px;} .list-head{font-size:12px; font-weight:700; margin:12px 0 8px;} .table-wrap{max-height:300px; overflow:auto; border:1px solid #f1f5f9; border-radius:10px;} .tbl{width:100%; border-collapse:collapse;} .tbl th{font-size:11px; color:#94a3b8; padding:10px 8px; text-align:left; border-bottom:1px solid #f1f5f9; background:#f8fafc;} .tbl td{padding:10px 8px; font-size:12px; border-bottom:1px solid #f8fafc;} .code-small{background:#eff6ff; color:#2563eb; padding:2px 6px; border-radius:6px; font-size:10px; font-weight:700;} .empty-row{text-align:center; color:#94a3b8; padding:20px;} .icon-btn{border:none; width:26px; height:26px; border-radius:6px; cursor:pointer; margin-right:4px;} .icon-btn.edit{background:#fef9c3;} .icon-btn.del{background:#ffe4e6;} .fw-bold{font-weight:700;}
.confirm-icon{width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 14px;} .confirm-icon.green{background:#d1fae5;} .confirm-icon.red{background:#fee2e2;} .confirm-icon.orange{background:#fef3c7;} .confirm-title{font-size:16px; font-weight:800; margin-bottom:6px;} .confirm-desc{font-size:12px; color:#64748b; margin-bottom:16px; line-height:1.5;} .confirm-actions{display:flex; gap:8px; justify-content:center;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px 18px; border-radius:10px; cursor:pointer;}
.req{color:#ef4444;} .phone-group{display:flex; align-items:center; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff;} .phone-group.error{border-color:#ef4444; background:#fef2f2;} .phone-prefix{background:#f1f5f9; padding:10px 12px; font-weight:800; color:#334155; border-right:1px solid #e2e8f0; font-size:13px;} .phone-inp{border:none !important; flex:1;} .err-msg{color:#ef4444; font-size:11px; margin-top:4px; display:block; font-weight:600;}
.hint-msg{color:#64748b; font-size:11px; margin-top:5px; display:block; line-height:1.4; background:#f8fafc; padding:6px 8px; border-radius:6px; border:1px dashed #e2e8f0;}
</style>
