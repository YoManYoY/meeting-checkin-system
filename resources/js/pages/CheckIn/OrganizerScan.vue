<template>
  <div class="org-page">
    <div class="top-bar">
      <div><h2>📋 ຕິດຕາມ Check-in – ສຳລັບຜູ້ຈັດງານ (ກໍລະນີ 2/3)</h2><small>ຜູ້ມາຮ່ວມສະແດງ QR / ບອກຊື່ → ຜູ້ຈັດງານຄົ້ນຫາ ແລະ ຢືນຢັນ</small></div>
      <select v-model="selectedMeetingId" @change="loadData" class="meeting-sel"><option value="">-- ເລືອກກອງປະຊຸມ --</option><option v-for="m in meetings" :key="m.id" :value="m.id">{{ m.meeting_code }} | {{ m.title }}</option></select>
    </div>
    <div v-if="selectedMeetingId" class="content">
      <div class="scan-box"><div class="scan-title">🔍 ສະແກນ QR / ຄົ້ນຫາ ຊື່ / ເບີໂທ ເພື່ອ Check-in</div><div class="scan-row"><input v-model="qrInput" @keyup.enter="lookupQR" class="scan-inp" placeholder="ພິມ ຊື່, ນາມສະກຸນ, ເບີໂທ, ຫຼື ວາງ Token QR..." /><button @click="lookupQR" class="scan-btn">ຄົ້ນຫາ</button><button @click="clearSearch" class="clear-btn">ລຶບ</button></div></div>

      <div v-if="foundReg" class="green-card">
        <div class="green-head"><div class="gh-left"><span class="check">✅</span> ພົບ: <b>{{ foundReg.name }} {{ foundReg.lastname }}</b><span class="sub">ຊື່: {{ foundReg.name }} {{ foundReg.lastname }} | ພາກສ່ວນ: {{ foundReg.organization }} | ຕຳແໜ່ງ: {{ foundReg.position }} | ເບີ: {{ foundReg.phone }}</span></div><span class="badge-org">{{ foundReg.organization }}</span></div>
        <div class="green-body">
          <label class="chk-yellow"><input type="checkbox" v-model="isSubstitute" /> ມາແທນຄົນອື່ນ (Substitute) – ຖ້າບໍ່ແມ່ນຄົນເດີມ</label>
          <div class="form-3col">
            <div class="col"><label class="lbl">ຊື່ <span class="req">*</span></label><input v-model="form.name" class="inp" :disabled="!isSubstitute" :class="{locked:!isSubstitute}" placeholder="ຊື່" /></div>
            <div class="col"><label class="lbl">ນາມສະກຸນ <span class="req">*</span></label><input v-model="form.lastname" class="inp" :disabled="!isSubstitute" :class="{locked:!isSubstitute}" placeholder="ນາມສະກຸນ" /></div>
            <div class="col"><label class="lbl">ພາກສ່ວນ / ອົງກອນ <span class="req">*</span></label><input v-model="form.organization" class="inp locked" disabled /></div>
            <div class="col"><label class="lbl">ຕຳແໜ່ງ <span class="req">*</span></label><input v-model="form.position" class="inp" :disabled="!isSubstitute" :class="{locked:!isSubstitute}" placeholder="ຕຳແໜ່ງ" /></div>
            <div class="col span2"><label class="lbl">ເບີໂທລະສັບ <span class="req">*</span></label><div class="phone-row"><span class="prefix">020</span><input v-model="phone8" @input="validatePhone" maxlength="8" class="inp phone" :disabled="!isSubstitute && !isEditingPhone" :class="{locked:!isSubstitute}" placeholder="5xxxxxxx" /></div><small class="hint-phone">💡 ກົດໄດ້ສະເພາະເລກ 8 ໂຕ, ຕ້ອງເລີ່ມດ້ວຍ 2, 5, 7, 8, 9 ເທົ່ານັ້ນ</small></div>
            <div v-if="isSubstitute" class="col span3"><label class="lbl">ໝາຍເຫດ <span class="req">*</span></label><input v-model="form.note" class="inp locked" disabled /></div>
          </div>
          <div class="sig-head"><label class="lbl">ເຊັນຊື່ (Signature) <span class="req">*</span></label><button @click="clearSig" type="button" class="clear">ລຶບລາຍເຊັນ</button></div>
          <div class="sig-box"><canvas ref="sigCanvas" @mousedown="startDraw" @mousemove="drawing" @mouseup="stopDraw" @mouseleave="stopDraw" @touchstart.prevent="startDraw" @touchmove.prevent="drawing" @touchend="stopDraw"></canvas></div>
          <button @click="confirmCheckin" :disabled="confirming" class="confirm-btn">{{ confirming ? 'ກຳລັງບັນທຶກ...' : (isSubstitute ? '✅ ຢືນຢັນ ມາແທນ & Check-in (ກໍລະນີ 3)' : '✅ ຢືນຢັນ Check-in (ກໍລະນີ 2)') }}</button>
          <div v-if="successMsg" class="ok">{{ successMsg }}</div><div v-if="errorMsg" class="err">❌ {{ errorMsg }}</div>
        </div>
      </div>

      <div class="stats-row"><span class="chip">ທັງໝົດ: <b>{{ stats.total }}</b></span><span class="chip green">Check-in ແລ້ວ: <b>{{ stats.checked }}</b></span><span class="chip yellow">ຍັງບໍ່ມາ: <b>{{ stats.pending }}</b></span></div>

      <div class="table-card"><h4>📋 ຍັງບໍ່ Check-in ({{ filteredPending.length }})</h4><table class="tbl"><thead><tr><th>ຊື່</th><th>ພາກສ່ວນ</th><th>ຕຳແໜ່ງ</th><th>ເບີໂທ</th><th>ເລືອກ</th></tr></thead><tbody><tr v-if="filteredPending.length===0"><td colspan="5" class="empty">ບໍ່ມີລາຍຊື່</td></tr><tr v-for="r in filteredPending" :key="r.id"><td><b>{{ r.name }} {{ r.lastname }}</b></td><td>{{ r.organization }}</td><td>{{ r.position }}</td><td>{{ r.phone }}</td><td><button @click="selectReg(r)" class="mini-btn">ເລືອກ</button></td></tr></tbody></table></div>

      <div class="table-card"><h4>✅ Check-in ແລ້ວ ({{ checkedList.length }})</h4><table class="tbl"><thead><tr><th>ຊື່</th><th>ພາກສ່ວນ</th><th>ຕຳແໜ່ງ</th><th>ເບີໂທ</th><th>ປະເພດ</th><th>ໝາຍເຫດ</th><th>ເວລາ</th></tr></thead><tbody><tr v-if="checkedList.length===0"><td colspan="7" class="empty">ຍັງບໍ່ມີ</td></tr><tr v-for="c in checkedList" :key="c.id"><td><b>{{ c.registration?.name }} {{ c.registration?.lastname }}</b></td><td>{{ c.registration?.organization }}</td><td>{{ c.registration?.position }}</td><td>{{ c.registration?.phone }}</td><td><span :class="'badge '+c.checkin_type">{{ c.checkin_type==='substituted' ? 'ມາແທນ' : c.checkin_type==='invited' ? 'ຜູ້ຖືກເຊີນ(invited)' : 'Walk-in' }}</span></td><td>{{ c.substitute_note || '-' }}</td><td>{{ formatDT(c.checked_in_at) }}</td></tr></tbody></table></div>
    </div>
  </div>
</template>
<script>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../utils/axios.js';
export default {
  setup(){
    const route=useRoute();
    const routeToken=ref(route.params.token||'');
    const meetings=ref([]); const selectedMeetingId=ref(''); const qrInput=ref(''); const foundReg=ref(null);
    const isSubstitute=ref(false); const form=ref({name:'',lastname:'',organization:'',position:'',phone:'',note:''}); const phone8=ref('');
    const pendingList=ref([]); const checkedList=ref([]); const confirming=ref(false); const errorMsg=ref(''); const successMsg=ref('');
    const sigCanvas=ref(null); const hasSig=ref(false); let ctx=null; let isDrawing=false; const isEditingPhone=ref(false);
    const stats=computed(()=>({total:pendingList.value.length+checkedList.value.length,checked:checkedList.value.length,pending:pendingList.value.length}));
    const filteredPending=computed(()=>{ if(!qrInput.value) return pendingList.value; const kw=qrInput.value.toLowerCase(); return pendingList.value.filter(r=> `${r.name} ${r.lastname} ${r.phone} ${r.organization}`.toLowerCase().includes(kw)); });
    const loadMeetings=async()=>{
      try{
        const res=await api.get('/api/meetings'); meetings.value=res.data.data||res.data||[];
        if(routeToken.value){ const m=meetings.value.find(x=>x.meeting_code===routeToken.value); if(m){ selectedMeetingId.value=m.id; await loadData(); } }
      }catch{}
    };
    const loadData=async()=>{ if(!selectedMeetingId.value) return; try{ const res=await api.get(`/api/meetings/${selectedMeetingId.value}/checkins-data`); pendingList.value=res.data.pending||[]; checkedList.value=res.data.checked||[]; }catch{} };
    const lookupQR=async()=>{ if(!qrInput.value||!selectedMeetingId.value) return; try{ const res=await api.get(`/api/meetings/${selectedMeetingId.value}/lookup`,{params:{token:qrInput.value}}); if(res.data.data) selectReg(res.data.data); }catch(e){ errorMsg.value=e.response?.data?.message||'ບໍ່ພົບ'; } };
    const selectReg=(r)=>{ foundReg.value=r; phone8.value=r.phone.replace('020','').slice(0,8); form.value={name:r.name,lastname:r.lastname||'',organization:r.organization,position:r.position,phone:phone8.value,note:''}; isSubstitute.value=false; isEditingPhone.value=false; nextTick(()=>{ initCanvas(); clearSig(); }); };
    const initCanvas=()=>{
      const canvas=sigCanvas.value; if(!canvas) return;
      const box=canvas.parentElement; canvas.width=box.clientWidth; canvas.height=160;
      canvas.style.width=box.clientWidth+'px'; canvas.style.height='160px';
      ctx=canvas.getContext('2d'); ctx.lineWidth=2; ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle='#000';
    };
    const getPos=(e)=>{
      const canvas=sigCanvas.value; const rect=canvas.getBoundingClientRect();
      if(e.touches&&e.touches[0]) return {x:e.touches[0].clientX-rect.left, y:e.touches[0].clientY-rect.top};
      return {x:e.offsetX!==undefined?e.offsetX:e.clientX-rect.left, y:e.offsetY!==undefined?e.offsetY:e.clientY-rect.top};
    };
    const startDraw=(e)=>{ isDrawing=true; const p=getPos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); };
    const drawing=(e)=>{ if(!isDrawing) return; const p=getPos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); hasSig.value=true; };
    const stopDraw=()=>{ isDrawing=false; };
    const clearSig=()=>{ if(!ctx) return; ctx.clearRect(0,0,sigCanvas.value.width,sigCanvas.value.height); hasSig.value=false; };
    const validatePhone=()=>{ phone8.value=phone8.value.replace(/\D/g,'').slice(0,8); form.value.phone=phone8.value; };
    const clearSearch=()=>{ qrInput.value=''; foundReg.value=null; isSubstitute.value=false; };
    const confirmCheckin=async()=>{
      errorMsg.value=''; successMsg.value='';
      if(!foundReg.value) return;
      if(phone8.value.length!==8){ errorMsg.value='ເບີໂທຕ້ອງ 8 ໂຕ'; return; }
      if(!['2','5','7','8','9'].includes(phone8.value[0])){ errorMsg.value='ເບີໂທຕ້ອງເລີ່ມດ້ວຍ 2,5,7,8,9'; return; }
      if(!form.value.name||!form.value.lastname||!form.value.organization||!form.value.position){ errorMsg.value='ກອກຂໍ້ມູນໃຫ້ຄົບ'; return; }
      if(!hasSig.value){ errorMsg.value='ກະລຸນາເຊັນຊື່'; return; }
      confirming.value=true;
      try{
        const sigData=sigCanvas.value.toDataURL('image/png');
        const res=await api.post(`/api/meetings/${selectedMeetingId.value}/organizer-checkin`,{
          registration_id:foundReg.value.id, is_substitute:isSubstitute.value,
          substitute_name:form.value.name, substitute_lastname:form.value.lastname,
          substitute_phone:'020'+phone8.value, substitute_position:form.value.position,
          substitute_organization:form.value.organization, substitute_note:form.value.note, signature:sigData
        });
        successMsg.value='✅ Check-in สำเร็จ: '+res.data.data.checkin_type;
        setTimeout(()=>{ foundReg.value=null; clearSearch(); loadData(); },1200);
      }catch(e){ errorMsg.value=e.response?.data?.message||'ผิดพลาด'; }finally{ confirming.value=false; }
    };
    const formatDT=(dt)=>{ try{ return new Date(dt).toLocaleString('lo-LA'); }catch{ return dt; } };
    watch(isSubstitute, (val)=>{ if(!foundReg.value) return; if(val){ form.value={name:'',lastname:'',organization:foundReg.value.organization,position:'',phone:'',note:`ມາແທນ ${foundReg.value.name} ${foundReg.value.lastname}`}; phone8.value=''; isEditingPhone.value=true; } else{ phone8.value=foundReg.value.phone.replace('020','').slice(0,8); form.value={name:foundReg.value.name,lastname:foundReg.value.lastname,organization:foundReg.value.organization,position:foundReg.value.position,phone:phone8.value,note:''}; isEditingPhone.value=false; } nextTick(()=>{ initCanvas(); clearSig(); }); });
    onMounted(loadMeetings);
    return {meetings,selectedMeetingId,qrInput,foundReg,isSubstitute,form,phone8,pendingList,checkedList,stats,filteredPending,confirming,errorMsg,successMsg,sigCanvas,isEditingPhone,loadData,lookupQR,selectReg,clearSearch,confirmCheckin,formatDT,startDraw,drawing,stopDraw,clearSig,validatePhone};
  }
}
</script>
<style scoped>
.org-page{padding:16px;max-width:1200px;margin:0 auto;font-family:'Noto Sans Lao',sans-serif;}
.top-bar{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;} .top-bar h2{margin:0;font-size:16px;} .top-bar small{color:#64748b;font-size:11px;} .meeting-sel{padding:9px 12px;border:1.5px solid #0f172a;border-radius:10px;min-width:320px;font-weight:600;}
.scan-box{background:#fff;border:2px solid #2563eb;border-radius:14px;padding:14px;margin-bottom:14px;} .scan-title{font-weight:700;margin-bottom:8px;font-size:13px;} .scan-row{display:flex;gap:8px;} .scan-inp{flex:1;padding:11px;border:1px solid #e2e8f0;border-radius:10px;} .scan-btn{background:#2563eb;color:#fff;border:none;padding:11px 16px;border-radius:10px;font-weight:700;cursor:pointer;} .clear-btn{background:#f1f5f9;border:1px solid #e2e8f0;padding:11px 12px;border-radius:10px;cursor:pointer;}
.green-card{background:#fff;border:2px solid #22c55e;border-radius:14px;overflow:hidden;margin-bottom:16px;}
.green-head{background:#f0fdf4;padding:12px 14px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #bbf7d0;} .gh-left{font-size:12px;line-height:1.5;} .gh-left b{color:#166534;} .sub{display:block;font-size:11px;color:#475569;margin-top:4px;} .badge-org{background:#dcfce7;padding:4px 8px;border-radius:6px;font-size:10px;font-weight:700;color:#166534;}
.green-body{padding:16px;}
.chk-yellow{background:#fef3c7;padding:8px 10px;border-radius:8px;font-size:12px;font-weight:600;display:flex;gap:6px;align-items:center;margin-bottom:12px;}
.form-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;} .col.span2{grid-column:span 2;} .col.span3{grid-column:span 3;}
.lbl{font-size:12px;font-weight:600;color:#334155;margin:0 0 5px;display:block;} .req{color:#ef4444;}
.inp{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:11px 12px;font-size:13px;outline:none;} .inp:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);} .locked:disabled{background:#f1f5f9;color:#475569;font-weight:600;opacity:.9;}
.phone-row{display:flex;gap:8px;align-items:center;} .prefix{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:13px;font-weight:700;color:#334155;} .phone{flex:1;} .hint-phone{font-size:10px;color:#64748b;display:block;margin-top:6px;}
.sig-head{display:flex;justify-content:space-between;align-items:center;margin-top:14px;} .clear{border:none;background:transparent;color:#ef4444;font-size:11px;font-weight:600;cursor:pointer;}
.sig-box{border:1.5px dashed #cbd5e1;border-radius:12px;height:160px;background:#fcfcfc;margin:6px 0 18px;overflow:hidden;} .sig-box canvas{width:100%;height:100%;cursor:crosshair;display:block;touch-action:none;}
.confirm-btn{width:100%;background:#16a34a;color:#fff;border:none;padding:13px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;}
.ok{background:#dcfce7;color:#166534;padding:10px;border-radius:10px;text-align:center;font-size:12px;margin-top:12px;border:1px solid #bbf7d0;} .err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:10px;text-align:center;font-size:12px;margin-top:12px;border:1px solid #fecaca;}
.stats-row{display:flex;gap:8px;margin:12px 0;} .chip{background:#fff;border:1px solid #e2e8f0;padding:6px 10px;border-radius:999px;font-size:11px;} .chip.green{background:#f0fdf4;border-color:#bbf7d0;} .chip.yellow{background:#fffbeb;border-color:#fde68a;}
.table-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:14px;overflow:auto;} .table-card h4{margin:0 0 10px;font-size:13px;font-weight:800;}
.tbl{width:100%;border-collapse:collapse;} .tbl th{font-size:11px;color:#94a3b8;text-align:left;padding:8px 6px;border-bottom:1px solid #f1f5f9;white-space:nowrap;} .tbl td{padding:8px 6px;font-size:12px;border-bottom:1px solid #f8fafc;}
.mini-btn{background:#2563eb;color:#fff;border:none;padding:5px 10px;border-radius:6px;font-size:11px;cursor:pointer;}
.badge{padding:2px 6px;border-radius:999px;font-size:10px;font-weight:700;} .badge.invited{background:#dbeafe;color:#1e40af;} .badge.substituted{background:#fef3c7;color:#92400e;} .badge.walkin{background:#dcfce7;color:#166534;}
.empty{text-align:center;padding:20px;color:#94a3b8;}
@media(max-width:800px){ .form-3col{grid-template-columns:1fr 1fr;} .col.span3{grid-column:span 2;} } @media(max-width:600px){ .form-3col{grid-template-columns:1fr;} .col.span2,.col.span3{grid-column:span 1;} }
</style>
