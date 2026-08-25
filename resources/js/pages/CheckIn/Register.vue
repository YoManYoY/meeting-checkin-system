<template>
  <div class="public-wrap">
    <div class="card">
      <div class="header">
        <h2>📋 ລົງທະບຽນ</h2>
        <div class="meeting-info" v-if="meeting">
          <b class="m-title">{{ meeting.title }}</b>
          <div class="m-date">📅 {{ formatDateLao(meeting.start_date) }} <span v-if="meeting.end_date && meeting.end_date!==meeting.start_date"> - {{ formatDateLao(meeting.end_date) }}</span> | ⏰ {{ fmtTime(meeting.start_time) }} - {{ fmtTime(meeting.end_time) }} | 📍 {{ meeting.location }}</div>
          <div class="m-code">{{ meeting.meeting_code }}</div>
        </div>
        <div class="meeting-info" v-else><b class="m-title">ກຳລັງໂຫຼດ...</b></div>
        <div class="badge-walkin">🚶 Walk-in / ບໍ່ໄດ້ຖືກເຊີນລ່ວງໜ້າ</div>
      </div>
      <div class="body">
        <div class="form-3col">
          <div class="col"><label class="lbl">ຊື່ <span class="req">*</span></label><input v-model="form.name" class="inp" placeholder="ຊື່" /></div>
          <div class="col"><label class="lbl">ນາມສະກຸນ <span class="req">*</span></label><input v-model="form.lastname" class="inp" placeholder="ນາມສະກຸນ" /></div>
          <div class="col"><label class="lbl">ພາກສ່ວນ / ອົງກອນ <span class="req">*</span></label><input v-model="form.organization" class="inp" placeholder="IICT, ກົມ..." /></div>
          <div class="col"><label class="lbl">ຕຳແໜ່ງ <span class="req">*</span></label><input v-model="form.position" class="inp" placeholder="ພະນັກງານ, ຫົວໜ້າ..." /></div>
          <div class="col span2"><label class="lbl">ເບີໂທລະສັບ <span class="req">*</span></label><div class="phone-row"><span class="prefix">020</span><input v-model="phone8" @input="validatePhone" maxlength="8" class="inp phone" placeholder="5xxxxxxx" /></div><small class="hint">💡 ກົດໄດ້ສະເພາະເລກ 8 ໂຕ, ຕ້ອງເລີ່ມດ້ວຍ 2, 5, 7, 8, 9 ເທົ່ານັ້ນ (ຕົວຢ່າງ: 020 5xxxxxxx)</small></div>
        </div>
        <div class="sig-head"><label class="lbl">ເຊັນຊື່ (Signature) <span class="req">*</span></label><button @click="clearSig" type="button" class="clear">ລຶບລາຍເຊັນ</button></div>
        <div class="sig-box"><canvas ref="sigCanvas" @mousedown="startDraw" @mousemove="drawing" @mouseup="stopDraw" @mouseleave="stopDraw" @touchstart="startDrawTouch" @touchmove="drawingTouch" @touchend="stopDraw" width="700" height="160"></canvas></div>
        <button @click="submit" :disabled="loading" class="btn">{{ loading ? 'ກຳລັງບັນທຶກ...' : 'ບັນທຶກ ແລະ Check-in' }}</button>
        <div v-if="msg" class="ok">{{ msg }}</div><div v-if="err" class="err">❌ {{ err }}</div>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../utils/axios.js';
export default {
  setup(){
    const route=useRoute(); const token=route.params.token; const meeting=ref(null);
    const form=ref({name:'',lastname:'',organization:'',position:''}); const phone8=ref('');
    const loading=ref(false); const msg=ref(''); const err=ref(''); const sigCanvas=ref(null); const hasSig=ref(false); let ctx=null; let isDrawing=false;
    const loadMeeting=async()=>{
      try{
        const res=await api.get(`/api/public/meetings/${token}`);
        const d=res.data;
        meeting.value = d?.data?.meeting || d?.meeting || d?.data || d;
      }catch(e){ err.value='ບໍ່ພົບກອງປະຊຸມ'; }
    };
    const formatDateLao=(d)=>{
      if(!d) return '';
      try{
        const dateStr = typeof d==='string' ? d.split('T')[0] : d;
        const dt = new Date(dateStr);
        if(isNaN(dt.getTime())) return dateStr;
        const monthsLao=['ມັງກອນ','ກຸມພາ','ມີນາ','ເມສາ','ພຶດສະພາ','ມິຖຸນາ','ກໍລະກົດ','ສິງຫາ','ກັນຍາ','ຕຸລາ','ພະຈິກ','ທັນວາ'];
        const day=dt.getDate();
        const month=monthsLao[dt.getMonth()];
        const year=dt.getFullYear();
        return `${day} ${month} ${year}`;
      }catch{ return typeof d==='string'?d:''; }
    };
    const fmtTime=(t)=>{ if(!t) return ''; return String(t).slice(0,5); };
    const initCanvas=()=>{ const c=sigCanvas.value; if(!c) return; ctx=c.getContext('2d'); ctx.lineWidth=2; ctx.lineCap='round'; ctx.strokeStyle='#0f172a'; c.style.touchAction='none'; };
    const getPos=(e,canvas)=>{ const rect=canvas.getBoundingClientRect(); const sx=canvas.width/rect.width; const sy=canvas.height/rect.height; const cx=e.touches?e.touches[0].clientX:e.clientX; const cy=e.touches?e.touches[0].clientY:e.clientY; return {x:(cx-rect.left)*sx,y:(cy-rect.top)*sy}; };
    const startDraw=(e)=>{ isDrawing=true; const p=getPos(e,sigCanvas.value); ctx.beginPath(); ctx.moveTo(p.x,p.y); hasSig.value=true; };
    const drawing=(e)=>{ if(!isDrawing) return; const p=getPos(e,sigCanvas.value); ctx.lineTo(p.x,p.y); ctx.stroke(); };
    const stopDraw=()=>{ if(isDrawing){ isDrawing=false; ctx.beginPath(); } };
    const startDrawTouch=(e)=>{ e.preventDefault(); startDraw(e); };
    const drawingTouch=(e)=>{ e.preventDefault(); drawing(e); };
    const clearSig=()=>{ const c=sigCanvas.value; if(ctx) ctx.clearRect(0,0,c.width,c.height); hasSig.value=false; };
    const validatePhone=()=>{ let v=phone8.value.replace(/\D/g,'').slice(0,8); if(v.length>0 && !['2','5','7','8','9'].includes(v[0])){ v=v.slice(1); } phone8.value=v; };
    const submit=async()=>{
      err.value=''; msg.value='';
      if(!form.value.name||!form.value.lastname||!form.value.organization||!form.value.position||!phone8.value){ err.value='ກອກຂໍ້ມູນໃຫ້ຄົບ'; return; }
      if(phone8.value.length!==8){ err.value='ເບີໂທຕ້ອງ 8 ໂຕ'; return; }
      if(!['2','5','7','8','9'].includes(phone8.value[0])){ err.value='ເບີໂທຕ້ອງເລີ່ມດ້ວຍ 2,5,7,8,9'; return; }
      if(!hasSig.value){ err.value='ກະລຸນາເຊັນຊື່'; return; }
      loading.value=true;
      try{
        const sigData=sigCanvas.value.toDataURL('image/png');
        await api.post(`/api/public/checkin/${token}`,{name:form.value.name,lastname:form.value.lastname,organization:form.value.organization,position:form.value.position,phone:'020'+phone8.value,signature:sigData});
        msg.value='✅ Check-in ສຳເລັດ ຂອບໃຈ!'; form.value={name:'',lastname:'',organization:'',position:''}; phone8.value=''; clearSig();
      }catch(e){ err.value=e.response?.data?.message||'ຜິດພາດ'; }finally{ loading.value=false; }
    };
    onMounted(async()=>{ await loadMeeting(); await nextTick(); initCanvas(); });
    return {meeting,form,phone8,loading,msg,err,sigCanvas,clearSig,startDraw,drawing,stopDraw,startDrawTouch,drawingTouch,validatePhone,submit,formatDateLao,fmtTime};
  }
}
</script>
<style scoped>
.public-wrap{display:flex;justify-content:center;padding:20px;background:#f8fafc;min-height:100vh;font-family:'Noto Sans Lao',sans-serif;}
.card{width:100%;max-width:820px;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.12);border:1px solid #e2e8f0;}
.header{background:#2563eb;padding:22px 20px;text-align:center;color:#fff;} .header h2{margin:0;font-size:18px;font-weight:800;}
.meeting-info{margin:10px 0;background:rgba(255,255,255,.15);border-radius:12px;padding:10px;} .m-title{font-size:15px;font-weight:800;display:block;} .m-date{font-size:12px;margin-top:6px;opacity:.95;line-height:1.4;} .m-code{font-size:10px;opacity:.7;margin-top:4px;}
.badge-walkin{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:7px 12px;border-radius:999px;font-size:11px;font-weight:700;display:inline-block;margin-top:10px;}
.body{padding:18px 20px 22px;}
.form-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;} .col.span2{grid-column:span 2;}
.lbl{font-size:12px;font-weight:600;color:#334155;margin:0 0 5px;display:block;} .req{color:#ef4444;}
.inp{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:11px 12px;font-size:13px;outline:none;} .inp:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);}
.phone-row{display:flex;gap:8px;align-items:center;} .prefix{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:13px;font-weight:700;color:#334155;} .phone{flex:1;} .hint{font-size:10px;color:#64748b;display:block;margin-top:6px;background:#f8fafc;border:1px dashed #e2e8f0;padding:6px 8px;border-radius:6px;}
.sig-head{display:flex;justify-content:space-between;align-items:center;margin-top:14px;} .clear{border:none;background:transparent;color:#ef4444;font-size:11px;font-weight:600;cursor:pointer;}
.sig-box{border:1.5px dashed #cbd5e1;border-radius:12px;height:160px;background:#fcfcfc;margin:6px 0 18px;overflow:hidden;} .sig-box canvas{width:100%;height:100%;cursor:crosshair;display:block;}
.btn{width:100%;background:#2563eb;color:#fff;border:none;padding:13px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;} .btn:disabled{opacity:.6;}
.ok{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;text-align:center;font-size:12px;margin-top:12px;border:1px solid #bbf7d0;} .err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;text-align:center;font-size:12px;margin-top:12px;border:1px solid #fecaca;}
@media(max-width:800px){ .form-3col{grid-template-columns:1fr 1fr;} } @media(max-width:600px){ .form-3col{grid-template-columns:1fr;} .col.span2{grid-column:span 1;} }
</style>
