<template>
  <div class="checkin-page">
    <div class="filter-card">
      <div class="filter-left">
        <h3>ຕິດຕາມການ Check-in ແລະ ລາຍຊື່ຜູ້ເຂົ້າຮ່ວມ</h3>
        <small>ຂໍ້ມູນຈາກຕາຕະລາງ <b>check_ins</b> • ເລືອກກອງປະຊຸມເພື່ອເບິ່ງ</small>
      </div>
      <div class="filter-right">
        <label class="sel-label">📌 ເລືອກກອງປະຊຸມ / ສຳມະນາ:</label>
        <select v-model="selectedMeetingId" @change="fetchCheckins" class="sel-meeting">
          <option value="">-- ເລືອກກອງປະຊຸມ --</option>
          <option v-for="m in meetings" :key="m.id" :value="m.id">
            {{ m.meeting_code }} | {{ m.title }}
          </option>
        </select>
      </div>
    </div>

    <!-- 4 Cards ขนาดเท่ารูป faa66e ไม่ใส่สี ขาวเรียบ -->
    <div v-if="selectedMeeting" class="stats-grid no-color">
      <div class="stat-card plain">
        <div class="card-top"><span class="icon-dot blue"></span><span class="label-inline">ຫົວຂໍ້ປະຊຸມ</span></div>
        <div class="stat-number">{{ selectedMeeting.title }}</div>
        <div class="stat-sub">{{ selectedMeeting.meeting_code }}</div>
      </div>
      <div class="stat-card plain">
        <div class="card-top"><span class="icon-dot green"></span><span class="label-inline">ຜູ້ CHECK-IN ທັງໝົດ (ຈາກ check_ins)</span></div>
        <div class="stat-number">{{ checkins.length }} / {{ registrations.length }} ຄົນ</div>
        <div class="stat-sub">{{ checkins.length }} ເຊັກອິນແລ້ວ • {{ registrations.length - checkins.length }} ຍັງບໍ່ມາ</div>
      </div>
      <div class="stat-card plain">
        <div class="card-top"><span class="icon-dot orange"></span><span class="label-inline">ສະຖານທີ່/ຫ້ອງປະຊຸມ</span></div>
        <div class="stat-number small">{{ selectedMeeting.location }}</div>
        <div class="stat-sub">{{ typeLabel(selectedMeeting.type) }} • {{ formatDate(selectedMeeting.start_date) }} {{ formatTime(selectedMeeting.start_time) }}-{{ formatTime(selectedMeeting.end_time) }}</div>
      </div>
      <div class="stat-card plain qr-card">
        <div class="card-top"><span class="label-inline">QR CODE ກອງປະຊຸມ</span></div>
        <div class="qr-row-simple">
          <button @click="showQR=true" class="btn-qr">🔍 ສະແກນ/ສະແດງ</button>
          <img v-if="qrImg" :src="qrImg" class="qr-mini" />
        </div>
        <div class="stat-sub">{{ selectedMeeting.meeting_code }}</div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-head">
        <h4>📋 ລາຍຊື່ຜູ້ເຂົ້າຮ່ວມກອງປະຊຸມ (ດຶງຈາກ check_ins)</h4>
        <div class="head-actions">
          <input v-model="search" placeholder="ຄົ້ນຫາ..." class="search-inp" />
          <button @click="exportCSV" class="btn-export csv">📄 Export CSV</button>
          <button @click="exportExcel" class="btn-export excel">📊 Export Excel</button>
        </div>
      </div>
      <table class="tbl">
        <thead><tr><th>ລຳດັບ</th><th>ຊື່ ແລະ ນາມສະກຸນ</th><th>ຕຳແໜ່ງ</th><th>ພາກສ່ວນ/ອົງກອນ</th><th>ເບີໂທ</th><th>ເວລາ Check-in</th><th>ລາຍເຊັນ</th></tr></thead>
        <tbody>
          <tr v-if="filteredCheckins.length===0"><td colspan="7" class="empty">ຍັງບໍ່ມີຂໍ້ມູນໃນ check_ins<br><small>ໄປ /checkin/{{ selectedMeeting?.meeting_code }} ເລືອກ ກໍລະນີ 2: ມີรายชื่อล่วงหน้า</small></td></tr>
          <tr v-for="(c,i) in filteredCheckins" :key="c.id"><td>{{ i+1 }}</td><td><b>{{ c.registration?.name }} {{ c.registration?.lastname }}</b><br><small class="code">{{ c.checkin_type }}</small></td><td>{{ c.registration?.position }}</td><td>{{ c.registration?.organization }}</td><td>{{ c.registration?.phone }}</td><td><span class="badge green">{{ formatDateTime(c.checked_in_at) }}</span></td><td><a v-if="c.signature_path" :href="`/storage/${c.signature_path}`" target="_blank" class="sig-link">🖊️ ເບິ່ງ</a><span v-else>-</span></td></tr>
        </tbody>
      </table>

      <!-- รักษาส่วนที่วงไว้ -->
      <div v-if="registrations.length>0" class="sub-table favorite-section">
        <h5>📝 ລາຍຊື່ຜູ້ຖືກເຊີນ (ຈາກ registrations) - {{ notCheckedIn.length }} ຄົນ - ຍັງບ່ Check-in: {{ notCheckedIn.length }}</h5>
        <table class="tbl small"><thead><tr><th>ຊື່</th><th>ພາກສ່ວນ</th><th>ເບີໂທ</th><th>ປະເພດ</th><th>ສະຖານະ</th></tr></thead><tbody><tr v-if="notCheckedIn.length===0"><td colspan="5" class="empty">ທຸກຄົນ Check-in ໝົດແລ້ວ ✅</td></tr><tr v-for="r in notCheckedIn" :key="r.id"><td><b>{{ r.name }} {{ r.lastname }}</b></td><td>{{ r.organization || r.department }}</td><td>{{ r.phone }}</td><td><span class="type-badge">{{ r.registration_type || 'invited' }}</span></td><td><span class="badge gray">{{ r.status || 'registered' }}</span></td></tr></tbody></table>
      </div>
    </div>

    <div v-if="showQR" class="modal-overlay" @click.self="showQR=false">
      <div class="modal-card qr"><div class="success">✅ QR Code - {{ selectedMeeting?.title }}</div><div class="qr-box"><img :src="qrImgBig" /></div><div class="link">{{ qrUrl }}</div><button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button><div class="row2 mt"><button class="btn-gray w100" @click="showQR=false">ປິດ</button><button class="btn-gray w100" @click="openCheckin">ເປີດໜ້າ Check-in</button></div></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../utils/axios.js'

const route = useRoute()
const meetings = ref([])
const selectedMeetingId = ref(route.query.meeting || '')
const selectedMeeting = ref(null)
const registrations = ref([])
const checkins = ref([])
const search = ref('')
const showQR = ref(false)
const qrUrl = ref('')
const qrImg = ref('')
const qrImgBig = ref('')

const typeLabel = t=>({meeting:'ກອງປະຊຸມ', seminar:'ສຳມະນາ', training:'ຝຶກອົບຮົມ', other:'ອື່ນໆ'}[t]||t)
const formatDate = d=>{ if(!d) return '-'; const s=String(d).slice(0,10); const [y,m,day]=s.split('-'); return `${day}/${m}/${y}` }
const formatTime = t=> String(t||'').slice(0,5)
const formatDateTime = dt=>{ if(!dt) return ''; try{ const d=new Date(dt); return d.toLocaleString('lo-LA') }catch{ return dt } }

const filteredCheckins = computed(()=>{
  if(!search.value) return checkins.value
  const kw = search.value.toLowerCase()
  return checkins.value.filter(c=>{ const r=c.registration||{}; return `${r.name} ${r.lastname} ${r.organization}`.toLowerCase().includes(kw) })
})
const notCheckedIn = computed(()=>{ const ids=new Set(checkins.value.map(c=>c.registration_id)); return registrations.value.filter(r=>!ids.has(r.id)) })

const fetchMeetings = async()=>{ try{ const res=await api.get('/api/meetings'); meetings.value=res.data.data||res.data||[]; if(selectedMeetingId.value){ selectedMeeting.value=meetings.value.find(m=>String(m.id)===String(selectedMeetingId.value))||null; if(selectedMeeting.value) updateQR() } }catch(e){} }
const fetchCheckins = async()=>{ if(!selectedMeetingId.value) return; selectedMeeting.value=meetings.value.find(m=>String(m.id)===String(selectedMeetingId.value))||null; if(!selectedMeeting.value) return; updateQR(); try{ const regRes=await api.get(`/api/meetings/${selectedMeetingId.value}/registrations`); registrations.value=regRes.data.data||regRes.data||[]; const checkRes=await api.get(`/api/checkins?meeting_id=${selectedMeetingId.value}`); checkins.value=checkRes.data.data||checkRes.data||[]; }catch(e){} }
const updateQR = ()=>{ if(!selectedMeeting.value) return; qrUrl.value=`${window.location.origin}/checkin/${selectedMeeting.value.meeting_code}`; const url=encodeURIComponent(qrUrl.value); qrImg.value=`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${url}`; qrImgBig.value=`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${url}` }
const copyLink = ()=>{ navigator.clipboard.writeText(qrUrl.value) }
const openCheckin = ()=> window.open(qrUrl.value,'_blank')
const exportCSV = ()=>{ if(selectedMeetingId.value) window.open(`/api/meetings/${selectedMeetingId.value}/export/csv`,'_blank') }
const exportExcel = ()=>{ if(selectedMeetingId.value) window.open(`/api/meetings/${selectedMeetingId.value}/export`,'_blank') }
watch(()=>route.query.meeting, v=>{ if(v){ selectedMeetingId.value=v; fetchCheckins() } })
onMounted(async()=>{ await fetchMeetings(); if(selectedMeetingId.value) fetchCheckins() })
</script>

<style scoped>
.checkin-page{padding:0;}
.filter-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;}
.filter-left h3{margin:0; font-size:14px; font-weight:800;} .filter-left small{font-size:11px; color:#64748b;} .filter-right{display:flex; flex-direction:column; gap:6px;} .sel-label{font-size:11px; color:#334155; font-weight:600;} .sel-meeting{border:2px solid #0f172a; border-radius:10px; padding:8px 12px; font-size:13px; min-width:380px; font-weight:600; background:#fff;}

/* ขนาดเท่าในรูป f62aef ไม่ใส่สี */
.stats-grid.no-color{display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px;} @media(max-width:1100px){ .stats-grid.no-color{grid-template-columns:repeat(2,1fr);} } @media(max-width:600px){ .stats-grid.no-color{grid-template-columns:1fr;} }
.stat-card.plain{background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:14px 16px; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,.06); display:flex; flex-direction:column; min-height:98px;}
.card-top{display:flex; align-items:center; gap:8px; margin-bottom:6px;}
.icon-dot{width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0;} .icon-dot.blue{background:#3b82f6;} .icon-dot.green{background:#10b981;} .icon-dot.orange{background:#f59e0b;}
.label-inline{font-size:11px; font-weight:600; color:#64748b; white-space:nowrap;}
.stat-number{font-size:16px; font-weight:800; margin:2px 0 4px; line-height:1.2; color:#0f172a;} .stat-number.small{font-size:14px;}
.stat-sub{font-size:10px; color:#94a3b8; line-height:1.3;}
.qr-card .qr-row-simple{display:flex; align-items:center; gap:8px; margin:6px 0 4px;} .btn-qr{background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; padding:5px 10px; border-radius:8px; font-size:11px; cursor:pointer; font-weight:600;} .qr-mini{width:36px; height:36px; border-radius:6px; border:1px solid #e5e7eb;}

.table-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:16px 18px;}
.table-head{display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;} .table-head h4{margin:0; font-size:14px; font-weight:800;} .head-actions{display:flex; gap:8px; align-items:center; flex-wrap:wrap;} .search-inp{border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px; font-size:12px; width:200px;} .btn-export{border:none; padding:7px 12px; border-radius:8px; font-size:11px; font-weight:600; cursor:pointer;} .btn-export.csv{background:#f1f5f9; color:#334155;} .btn-export.excel{background:#10b981; color:#fff;}
.tbl{width:100%; border-collapse:collapse;} .tbl th{font-size:11px; color:#94a3b8; text-align:left; padding:10px 6px; border-bottom:1px solid #f1f5f9;} .tbl td{padding:10px 6px; font-size:12px; border-bottom:1px solid #f8fafc;} .code{font-size:10px; color:#94a3b8;} .badge.green{background:#dcfce7; color:#065f46; padding:4px 8px; border-radius:10px; font-size:11px; font-weight:700;} .badge.gray{background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:10px; font-size:11px;} .type-badge{background:#eff6ff; color:#2563eb; padding:3px 8px; border-radius:8px; font-size:10px;} .sig-link{background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; padding:4px 8px; border-radius:6px; font-size:11px; text-decoration:none;} .empty{text-align:center; padding:30px; color:#94a3b8; line-height:1.6;}
.favorite-section{margin-top:20px; padding-top:16px; border-top:2px dashed #e2e8f0; background:#f8fafc; border-radius:12px; padding:14px; border:1px solid #e2e8f0; border-top-style:dashed;}
.favorite-section h5{margin:0 0 10px; font-size:13px; font-weight:800; color:#0f172a;}
.tbl.small{margin-top:8px;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:200;} .modal-card.qr{width:400px; background:#fff; border-radius:16px; padding:20px; text-align:center;} .success{background:#dcfce7; color:#166534; padding:8px; border-radius:10px; font-weight:700; margin-bottom:12px;} .qr-box{border:1px solid #f1f5f9; border-radius:14px; padding:14px; margin:14px 0; display:flex; justify-content:center;} .link{background:#f8fafc; padding:10px; border-radius:8px; font-size:11px; word-break:break-all; margin-bottom:10px;} .w100{width:100%;} .mt{margin-top:10px;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:8px;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px; border-radius:10px; cursor:pointer; width:100%;} .btn-gray{background:#f1f5f9; border:none; padding:10px; border-radius:10px; width:100%; cursor:pointer; font-size:12px;}
</style>
