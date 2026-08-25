<template>
  <div class="dashboard-page">
    <div class="stats-grid">
      <!-- ทั้ง 4 card กดได้ไป meetings -->
      <router-link to="/meetings" class="stat-card blue">
        <div class="card-top"><span class="icon-box">📄</span><span class="label-inline">ກອງປະຊຸມທັງໝົດ</span></div>
        <div class="stat-number">{{ stats.meetings || 0 }}</div>
        <div class="stat-sub">ຂໍ້ມູນເດືອນ ສິງຫາ 2026</div>
      </router-link>
      <router-link to="/meetings?type=seminar" class="stat-card purple">
        <div class="card-top"><span class="icon-box">🎓</span><span class="label-inline">ສຳມະນາທັງໝົດ</span></div>
        <div class="stat-number">{{ stats.seminars || 0 }}</div>
        <div class="stat-sub">ຂໍ້ມູນເດືອນ ສິງຫາ 2026</div>
      </router-link>
      <router-link to="/meetings?type=training" class="stat-card green">
        <div class="card-top"><span class="icon-box">📚</span><span class="label-inline">ຝຶກອົບຮົມທັງໝົດ</span></div>
        <div class="stat-number">{{ stats.trainings || 0 }}</div>
        <div class="stat-sub">ຂໍ້ມູນເດືອນ ສິງຫາ 2026</div>
      </router-link>
      <router-link to="/checkins" class="stat-card pink">
        <div class="card-top"><span class="icon-box">✅</span><span class="label-inline">ເຊັກອິນທັງໝົດ</span></div>
        <div class="stat-number">{{ stats.checkins || 0 }}</div>
        <div class="stat-sub">ຂໍ້ມູນເດືອນ ສິງຫາ 2026</div>
      </router-link>
    </div>

    <div class="bottom-grid">
      <div class="table-card">
        <div class="table-head">
          <div><h4>ກອງປະຊຸມລ່າສຸດ (ສຳເລັດກອງປະຊຸມ)</h4><small>ລາຍການ 5 ກອງປະຊຸມທີ່ສຳເລັດແລ້ວລ່າສຸດໃນລະບົບ</small></div>
          <router-link to="/meetings" class="link-all">ເບິ່ງທັງໝົດ →</router-link>
        </div>
        <table class="tbl">
          <thead><tr><th>ຫົວຂໍ້ກອງປະຊຸມ</th><th>ສະຖານທີ່ / ປະເພດ</th><th>ວັນເລີ່ມ</th><th>ຜູ້ເຂົ້າຮ່ວມ</th><th>QR CODE</th></tr></thead>
          <tbody>
            <tr v-if="completedMeetings.length===0"><td colspan="5" class="empty">ຍັງບໍ່ມີກອງປະຊຸມທີ່ສຳເລັດ</td></tr>
            <tr v-for="m in completedMeetings" :key="m.id">
              <td><b>{{ m.title }}</b><br><small class="code">{{ m.meeting_code }}</small></td>
              <td><span class="loc-badge">📍 {{ m.location }} • {{ typeLabel(m.type) }}</span></td>
              <td>{{ formatDate(m.start_date) }}<br><small>{{ formatTime(m.start_time) }}</small></td>
              <td><span class="count-green">{{ m.registrations_count || 0 }} ຄົນ</span></td>
              <td><button @click="viewQR(m)" class="qr-btn">QR Code</button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="right-col">
        <div class="quick-card">
          <h4>ເມນູດ່ວນ (Quick Actions)</h4>
          <router-link to="/meetings/create" class="quick-btn blue"><span class="qb-icon blue">+</span><span>ສ້າງກອງປະຊຸມໃໝ່</span><span class="arrow">→</span></router-link>
          <router-link to="/checkins" class="quick-btn green"><span class="qb-icon green">✓</span><span>ເປີດໜ້າ Check-in</span><span class="arrow">→</span></router-link>
        </div>

        <!-- กล่องกำลังดำเนินงาน กดได้ไป meetings -->
        <router-link to="/meetings?status=ongoing" class="ready-card-link">
          <div class="ready-card">
            <small class="ready-label">SYSTEM READY</small>
            <h4 class="ready-title">ລະບົບພ້ອມໃຊ້ງານແລ້ວ</h4>
            <p class="ready-desc">ພ້ອມສຳລັບການສ້າງກອງປະຊຸມ ແລະ ສະແກນ QR Code ເພື່ອເຂົ້າກອງປະຊຸມ.<br>ປະຈຳເດືອນ ສິງຫາ 2026</p>
            <div class="ongoing-box">
              <div class="ongoing-label">🟢 ກຳລັງດຳເນີນງານກອງປະຊຸມ / ສຳມະນາ :</div>
              <div class="ongoing-number">{{ ongoingCount }} ກອງປະຊຸມ</div>
              <div class="ongoing-sub">ກຳລັງດຳເນີນງານຢູ່ຕອນນີ້ • ກົດເພື່ອເບິ່ງ →</div>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <div v-if="showQR" class="modal-overlay" @click.self="showQR=false">
      <div class="modal-card qr"><div class="success">✅ QR Code - {{ selected?.title }}</div><div class="qr-box"><img :src="qrImg" /></div><div class="link">{{ qrUrl }}</div><button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button><div class="row2 mt"><button class="btn-gray w100" @click="showQR=false">ປິດ</button><button class="btn-gray w100" @click="openCheckin">ເປີດ Check-in</button></div></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../utils/axios.js'

const stats = ref({ meetings:0, seminars:0, trainings:0, checkins:0 })
const allMeetings = ref([])
const showQR = ref(false)
const selected = ref(null)
const qrUrl = ref('')
const qrImg = ref('')

const typeLabel = t=>({meeting:'ກອງປະຊຸມ', seminar:'ສຳມະນາ', training:'ຝຶກອົບຮົມ', other:'ອື່ນໆ'}[t]||t)
const formatDate = d=>{ if(!d) return '-'; const s=String(d).slice(0,10); const [y,m,day]=s.split('-'); return `${day}/${m}/${y}` }
const formatTime = t=> String(t||'').slice(0,5)

const completedMeetings = computed(()=>{
  return allMeetings.value.filter(m=>{
    const now = new Date()
    try{
      const e = new Date(String(m.end_date).slice(0,10)+'T'+String(m.end_time).slice(0,5))
      if(m.status==='completed') return true
      return now > e
    }catch{ return m.status==='completed' }
  }).sort((a,b)=> b.id - a.id).slice(0,5)
})

const ongoingCount = computed(()=>{
  return allMeetings.value.filter(m=>{
    const now = new Date()
    try{
      const s = new Date(String(m.start_date).slice(0,10)+'T'+String(m.start_time).slice(0,5))
      const e = new Date(String(m.end_date).slice(0,10)+'T'+String(m.end_time).slice(0,5))
      if(m.status==='completed') return false
      return now >= s && now <= e
    }catch{ return m.status==='ongoing' }
  }).length
})

const fetchData = async()=>{
  try{
    const res = await api.get('/api/meetings')
    const meetings = res.data.data || res.data || []
    const arr = Array.isArray(meetings) ? meetings : []
    allMeetings.value = arr
    stats.value.meetings = arr.filter(m=>m.type==='meeting').length
    stats.value.seminars = arr.filter(m=>m.type==='seminar').length
    stats.value.trainings = arr.filter(m=>m.type==='training').length
    try{
      const dash = await api.get('/api/dashboard/stats')
      const d = dash.data.data || dash.data
      if(d){ stats.value.checkins = d.total_checkins || d.checkins || 0 }
    }catch{}
  }catch(e){ console.error(e) }
}

const viewQR = m=>{ selected.value=m; qrUrl.value=`${window.location.origin}/checkin/${m.meeting_code}`; qrImg.value=`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(qrUrl.value)}`; showQR.value=true }
const copyLink = ()=>{ navigator.clipboard.writeText(qrUrl.value) }
const openCheckin = ()=> window.open(qrUrl.value,'_blank')
onMounted(fetchData)
</script>

<style scoped>
.dashboard-page{padding:0;}
.stats-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px;} @media(max-width:1100px){ .stats-grid{grid-template-columns:repeat(2,1fr);} } @media(max-width:600px){ .stats-grid{grid-template-columns:1fr;} }
.stat-card{border-radius:18px; padding:18px 20px; color:#fff; position:relative; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.12); text-decoration:none; display:flex; flex-direction:column; transition:transform .15s ease, box-shadow .15s ease; cursor:pointer;}
.stat-card:hover{transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.2);}
.stat-card.blue{background:linear-gradient(135deg,#2563eb,#3b82f6);} .stat-card.purple{background:linear-gradient(135deg,#8b5cf6,#a78bfa);} .stat-card.green{background:linear-gradient(135deg,#059669,#10b981);} .stat-card.pink{background:linear-gradient(135deg,#db2777,#ec4899);}
.card-top{display:flex; align-items:center; gap:10px;} .icon-box{width:36px; height:36px; background:rgba(255,255,255,.25); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px;} .label-inline{font-size:13px; font-weight:700; opacity:.95;}
.stat-number{font-size:38px; font-weight:800; margin:14px 0 4px; text-align:center;} .stat-sub{font-size:10px; opacity:.8; text-align:center;}

.bottom-grid{display:grid; grid-template-columns:1fr 320px; gap:16px;} @media(max-width:1000px){ .bottom-grid{grid-template-columns:1fr;} }
.table-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:16px 18px;}
.table-head{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; border-bottom:1px solid #f1f5f9; padding-bottom:12px; margin-bottom:10px;} .table-head h4{margin:0; font-size:14px; font-weight:800;} .table-head small{font-size:11px; color:#64748b;} .link-all{background:#f8fafc; border:1px solid #e2e8f0; padding:6px 10px; border-radius:8px; font-size:11px; text-decoration:none; color:#2563eb;}
.tbl{width:100%; border-collapse:collapse;} .tbl th{font-size:11px; color:#94a3b8; text-align:left; padding:10px 6px; border-bottom:1px solid #f8fafc;} .tbl td{padding:12px 6px; font-size:12px; border-bottom:1px solid #f8fafc;} .code{font-size:10px; color:#94a3b8;} .loc-badge{background:#eff6ff; color:#2563eb; padding:3px 8px; border-radius:12px; font-size:11px;} .count-green{background:#dcfce7; color:#065f46; padding:3px 8px; border-radius:12px; font-size:11px;} .qr-btn{background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; padding:4px 10px; border-radius:8px; font-size:11px; cursor:pointer;} .empty{text-align:center; padding:20px; color:#94a3b8;}
.right-col{display:flex; flex-direction:column; gap:16px;}
.quick-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:16px;} .quick-card h4{margin:0 0 12px; font-size:13px; font-weight:800;} .quick-btn{display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:12px; text-decoration:none; margin-bottom:10px; font-size:13px; font-weight:600; justify-content:space-between;} .quick-btn.blue{background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe;} .quick-btn.green{background:#f0fdf4; color:#166534; border:1px solid #dcfce7;} .qb-icon{width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff;} .qb-icon.blue{background:#2563eb;} .qb-icon.green{background:#16a34a;} .arrow{margin-left:auto; opacity:.6;}
.ready-card-link{text-decoration:none; display:block;}
.ready-card{background:#0f172a; color:#fff; border-radius:16px; padding:18px; transition:transform .15s ease, box-shadow .15s ease; cursor:pointer; border:1px solid #1e293b;}
.ready-card-link:hover .ready-card{transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.25); border-color:#34d399;}
.ready-label{color:#34d399; font-size:10px; letter-spacing:1px; font-weight:700;} .ready-title{margin:8px 0; font-size:14px; font-weight:800;} .ready-desc{font-size:11px; color:#94a3b8; line-height:1.5; margin:0;}
.ongoing-box{background:#1e293b; border:1px solid #334155; border-radius:12px; padding:14px; margin-top:14px;} .ongoing-label{font-size:11px; color:#94a3b8;} .ongoing-number{font-size:28px; font-weight:800; color:#34d399; margin:6px 0;} .ongoing-sub{font-size:10px; color:#64748b;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:200;} .modal-card.qr{width:400px; background:#fff; border-radius:16px; padding:20px; text-align:center;} .success{background:#dcfce7; color:#166534; padding:8px; border-radius:10px; font-weight:700; margin-bottom:12px;} .qr-box{border:1px solid #f1f5f9; border-radius:14px; padding:14px; margin:14px 0; display:flex; justify-content:center;} .link{background:#f8fafc; padding:10px; border-radius:8px; font-size:11px; word-break:break-all; margin-bottom:10px;} .w100{width:100%;} .mt{margin-top:10px;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:8px;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px; border-radius:10px; cursor:pointer; width:100%;} .btn-gray{background:#f1f5f9; border:none; padding:10px; border-radius:10px; width:100%; cursor:pointer; font-size:12px;}
</style>
