<template>
  <div class="container py-4" style="max-width: 600px;">
    <div class="card shadow-sm">
      <div class="card-body p-4 text-center">
        <h5 class="fw-bold mb-3">ເຊັນຊື່ເຂົ້າຮ່ວມ ✍️</h5>
        <p class="text-muted small">ກະລຸນາເຊັນຊື່ໃນກ່ອບລຸ່ມນີ້</p>

        <canvas ref="canvas" width="500" height="200"
          style="border:2px dashed #ccc; width:100%; touch-action:none; border-radius:8px;"
          @mousedown="startDraw" @mousemove="draw" @mouseup="stopDraw"
          @touchstart.prevent="startDraw" @touchmove.prevent="draw" @touchend="stopDraw">
        </canvas>

        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-outline-secondary w-50" @click="clear">ລົບເຊັນໃໝ່</button>
          <button class="btn btn-primary w-50" @click="submit" :disabled="submitting">
            {{ submitting? 'ກຳລັງບັນທຶກ...' : 'ຢືນຢັນການເຂົ້າຮ່ວມ' }}
          </button>
        </div>

        <div v-if="error" class="alert alert-danger mt-3 py-2 small">{{ error }}</div>
        <div v-if="done" class="alert alert-success mt-3">
          ✅ ເຊັກອິນ ແລະ ບັນທຶກລາຍເຊັນສຳເລັດແລ້ວ!
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  data() {
    return {
      token: this.$route.params.token, // 👈 ເອົາ token ຈາກ URL
      registrationId: this.$route.params.registrationId,
      drawing: false,
      ctx: null,
      submitting: false,
      error: '',
      done: false,
    }
  },
  mounted() {
    this.ctx = this.$refs.canvas.getContext('2d');
    this.ctx.lineWidth = 2;
    this.ctx.lineCap = 'round';
    this.ctx.strokeStyle = '#000';
  },
  methods: {
    getPos(e) {
      const rect = this.$refs.canvas.getBoundingClientRect();
      const t = e.touches? e.touches[0] : e;
      const scaleX = 500 / rect.width;
      const scaleY = 200 / rect.height;
      return {
        x: (t.clientX - rect.left) * scaleX,
        y: (t.clientY - rect.top) * scaleY
      };
    },
    startDraw(e) {
      this.drawing = true;
      const {x,y} = this.getPos(e);
      this.ctx.beginPath();
      this.ctx.moveTo(x,y);
    },
    draw(e) {
      if (!this.drawing) return;
      const {x,y} = this.getPos(e);
      this.ctx.lineTo(x,y);
      this.ctx.stroke();
    },
    stopDraw() { this.drawing = false; },
    clear() {
      this.ctx.clearRect(0,0,500,200);
      this.error = '';
      this.done = false;
    },
    async submit() {
      this.submitting = true;
      this.error = '';
      try {
        const dataUrl = this.$refs.canvas.toDataURL('image/png');
        // 👈 ຖືກຕາມ spec: POST /api/public/checkin/{token}/signature + registration_id + signature
        const res = await axios.post(`/api/public/checkin/${this.token}/signature`, {
          registration_id: this.registrationId,
          signature: dataUrl
        });
        console.log(res.data);
        this.done = true;
      } catch (err) {
        if (err.response?.status === 400) {
          this.error = 'ທ່ານໄດ້ Check-in ແລ້ວ - ປ້ອງກັນ Duplicate ສຳເລັດ!';
        } else {
          this.error = err.response?.data?.message || 'ເກີດຂໍ້ຜິດພາດ';
        }
      } finally {
        this.submitting = false;
      }
    }
  }
}
</script>
