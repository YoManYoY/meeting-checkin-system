<template>
  <div class="detail-page">
    <div v-if="loading" class="loading">⏳ ກຳລັງໂຫຼດ...</div>

    <div v-else-if="meeting">
      <div class="head-main">
        <div>
          <h3 class="title">{{ meeting.title }}</h3>
          <div class="sub-line">
            <span class="code">{{ meeting.meeting_code }}</span>
            <span class="dot">•</span>
            <span class="badge" :class="meeting.type">{{ typeLabel(meeting.type) }}</span>
            <span class="dot">•</span>
            <span>📍 {{ meeting.location }}</span>
          </div>
        </div>
        <!-- ย้ายปุ่มกลับมาอยู่ข้างๆ แก้ไข -->
        <div class="head-actions">
          <router-link to="/meetings" class="btn-back">← ກັບຄືນລາຍການ</router-link>
          <router-link :to="`/meetings/${meeting.id}/edit`" class="btn-edit">✏️ ແກ້ໄຂ</router-link>
          <button @click="showQR=true" class="btn-qr">📷 QR Code</button>
        </div>
      </div>

      <div class="grid2">
        <div class="info-card">
          <h4>ຂໍ້ມູນກອງປະຊຸມ</h4>
          <ul class="info-list">
            <li><b>ລະຫັດ:</b> <code class="code-pink">{{ meeting.meeting_code }}</code></li>
            <li><b>ຫົວຂໍ້:</b> {{ meeting.title }}</li>
            <li><b>ປະເພດ:</b> <span class="badge" :class="meeting.type">{{ typeLabel(meeting.type) }}</span></li>
            <li><b>ລາຍລະອຽດ:</b> {{ meeting.description || '-' }}</li>
            <li><b>ສະຖານທີ່:</b> 📍 {{ meeting.location }}</li>
            <li><b>ວັນເວລາ:</b> {{ formatDate(meeting.start_date) }} {{ formatTime(meeting.start_time) }} - {{ formatDate(meeting.end_date) }} {{ formatTime(meeting.end_time) }}</li>
            <li><b>ສະຖານະ:</b> <span class="status" :class="autoStatus">{{ autoStatusLabel }}</span></li>
            <li><b>ຜູ້ເຂົ້າຮ່ວມ:</b> {{ meeting.registrations_count ?? 0 }} ຄົນ</li>
          </ul>
          <!-- <div class="actions"><a :href="`/api/meetings/${meeting.id}/export`" target="_blank" class="btn-export">📥 Export Excel</a></div> -->
        </div>
        <div class="qr-card">
          <h4>QR Code ສຳລັບ Check-in</h4>
          <div class="qr-box"><img :src="qrImg" alt="QR" /></div>
          <div class="link">{{ qrUrl }}</div>
          <div class="qr-actions">
            <button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button>
            <div class="row2 mt"><button class="btn-gray w100" @click="openCheckin">ເປີດ Check-in</button><router-link :to="`/checkins?meeting=${meeting.id}`" class="btn-gray w100 link-btn">ເບິ່ງ Check-in</router-link></div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showQR" class="modal-overlay" @click.self="showQR=false">
      <div class="modal-card qr"><div class="success">✅ QR Code - {{ meeting?.title }}</div><div class="qr-box"><img :src="qrImg" /></div><div class="link">{{ qrUrl }}</div><button class="btn-blue w100" @click="copyLink">📋 ກ໋ອບປີ້ລິ້ງ</button><div class="row2 mt"><button class="btn-gray w100" @click="showQR=false">ປິດ</button><button class="btn-gray w100" @click="openCheckin">ເປີດ Check-in</button></div></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../utils/axios.js'
const route = useRoute()
const id = route.params.id
const meeting = ref(null)
const loading = ref(true)
const qrUrl = ref('')
const qrImg = ref('')
const showQR = ref(false)
const typeLabel = t=>({meeting:'ກອງປະຊຸມ', seminar:'ສຳມະນາ', training:'ຝຶກອົບຮົມ', other:'ອື່ນໆ'}[t]||t)
const formatDate = d=>{ if(!d) return '-'; const s=String(d).slice(0,10); const [y,m,day]=s.split('-'); return `${day}/${m}/${y}` }
const formatTime = t=> String(t||'').slice(0,5)
const autoStatus = computed(()=>{
  if(!meeting.value) return 'scheduled'
  try{
    const now = new Date()
    const s = new Date(String(meeting.value.start_date).slice(0,10)+'T'+String(meeting.value.start_time).slice(0,5))
    const e = new Date(String(meeting.value.end_date).slice(0,10)+'T'+String(meeting.value.end_time).slice(0,5))
    if(meeting.value.status==='completed') return 'completed'
    if(now < s) return 'scheduled'
    if(now>=s && now<=e) return 'ongoing'
    return 'completed'
  }catch{ return meeting.value.status }
})
const autoStatusLabel = computed(()=>({scheduled:'ລໍຖ້າເລີ່ມ', ongoing:'ດຳເນີນງານ', completed:'ສຳເລັດແລ້ວ'}[autoStatus.value]||autoStatus.value))
const fetchDetail = async()=>{
  try{
    const res = await api.get(`/api/meetings/${id}`)
    meeting.value = res.data.data || res.data
    qrUrl.value=`${window.location.origin}/checkin/${meeting.value.meeting_code}`
    qrImg.value=`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(qrUrl.value)}`
  }catch(e){ console.error(e) }
  finally{ loading.value=false }
}
const copyLink = ()=>{ navigator.clipboard.writeText(qrUrl.value) }
const openCheckin = ()=> window.open(qrUrl.value,'_blank')
onMounted(fetchDetail)
</script>

<style scoped>
.detail-page{max-width:1000px; margin:0 auto; padding:0;}
.head-main{display:flex; justify-content:space-between; gap:12px; margin-bottom:18px; flex-wrap:wrap; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px 18px;}
.title{margin:0; font-size:20px; font-weight:800; color:#0f172a;} .sub-line{margin-top:6px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:#64748b;} .code{background:#eff6ff; color:#2563eb; padding:2px 8px; border-radius:6px; font-weight:800; font-size:11px;} .code-pink{color:#db2777; background:#fdf2f8; padding:2px 6px; border-radius:6px;} .dot{color:#cbd5e1;}
.badge{padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; border:1px solid #e2e8f0;} .badge.meeting{background:#dbeafe; color:#1d4ed8;} .badge.seminar{background:#fef9c3; color:#854d0e;} .badge.training{background:#dcfce7; color:#166534;}
.head-actions{display:flex; gap:8px; align-items:center; flex-wrap:wrap;} .btn-back{background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; padding:8px 14px; border-radius:10px; text-decoration:none; font-weight:600; font-size:12px;} .btn-edit{background:#fef9c3; color:#854d0e; border:1px solid #fde68a; padding:8px 14px; border-radius:10px; text-decoration:none; font-weight:600; font-size:12px;} .btn-qr{background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; padding:8px 14px; border-radius:10px; font-weight:600; font-size:12px; cursor:pointer;}
.loading{text-align:center; padding:40px; color:#64748b;} .grid2{display:grid; grid-template-columns:1fr 380px; gap:16px;} @media(max-width:900px){ .grid2{grid-template-columns:1fr;} }
.info-card, .qr-card{background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:20px;} .info-card h4, .qr-card h4{font-size:14px; font-weight:800; margin:0 0 12px;} .info-list{list-style:none; padding:0; margin:0;} .info-list li{padding:9px 0; border-bottom:1px solid #f8fafc; font-size:13px;} .status{padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700;} .status.scheduled{background:#fef3c7; color:#92400e;} .status.ongoing{background:#dcfce7; color:#065f46;} .status.completed{background:#f1f5f9; color:#475569;}
.actions{margin-top:14px;} .btn-export{background:#10b981; color:#fff; padding:8px 14px; border-radius:10px; text-decoration:none; font-size:12px; font-weight:600;}
.qr-box{border:1px solid #f1f5f9; border-radius:14px; padding:14px; margin:12px 0; display:flex; justify-content:center;} .link{background:#f8fafc; padding:10px; border-radius:8px; font-size:11px; word-break:break-all; margin-bottom:10px;} .w100{width:100%;} .mt{margin-top:10px;} .row2{display:grid; grid-template-columns:1fr 1fr; gap:8px;} .btn-blue{background:#2563eb; color:#fff; border:none; padding:10px; border-radius:10px; cursor:pointer; width:100%;} .btn-gray{background:#f1f5f9; border:none; padding:10px; border-radius:10px; width:100%; text-align:center; font-size:12px; cursor:pointer; text-decoration:none; color:#334155; display:flex; align-items:center; justify-content:center;} .link-btn{color:#334155;}
.modal-overlay{position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:200;} .modal-card.qr{width:400px; background:#fff; border-radius:16px; padding:20px; text-align:center;} .success{background:#dcfce7; color:#166534; padding:8px; border-radius:10px; font-weight:700; margin-bottom:12px;}
</style>
