<template>
  <div class="users-page">
    <div class="card">
      <div class="card-head">
        <div>
          <h5>ຈັດການຜູ້ໃຊ້ງານ</h5>
          <small class="text-muted"
            >ລາຍຊື່ຜູ້ໃຊ້ ແລະ ສິດທິການເຂົ້າເຖິງລະບົບ</small
          >
        </div>
      </div>
      <div class="search-wrap">
        <span>🔍</span
        ><input
          v-model="search"
          placeholder="ຄົ້ນຫາເບີ, Username, ລະຫັດ ID..."
        />
      </div>
      <table class="tbl">
        <thead>
          <tr>
            <th>USER ID</th>
            <th>USERNAME</th>
            <th>PASSWORD</th>
            <th>ຊື່ ນາມສະກຸນ</th>
            <th>ສິດ (ROLE)</th>
            <th>ວັນສ້າງ</th>
            <th>ຈັດການ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in filtered" :key="u.id">
            <td>
              <span class="uid">{{ u.user_code || u.id }}</span>
            </td>
            <td class="fw-bold">{{ u.username }}</td>
            <td>{{ u.plain_password || "••••••" }}</td>
            <td>{{ u.name }}</td>
            <td>
              <span class="role" :class="u.role">{{
                u.role?.toUpperCase()
              }}</span>
            </td>
            <td>{{ fmt(u.created_at) }}</td>
            <td>
              <button class="icon-btn">✏️</button
              ><button class="icon-btn del" @click="delUser(u.id)">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<script>
import { ref, computed, onMounted } from "vue";
import api from "../../utils/axios";
export default {
  setup() {
    const users = ref([]);
    const search = ref("");
    const fmt = (d) =>
      d ? new Date(d).toISOString().split("T")[0] : "2026-08-16";
    const filtered = computed(() =>
      users.value.filter((u) =>
        (u.username + u.name + u.user_code)
          .toLowerCase()
          .includes(search.value.toLowerCase())
      )
    );
    const load = async () => {
      try {
        const r = await api.get("/api/users");
        const d = r.data.data?.data || r.data.data || r.data;
        users.value = Array.isArray(d) ? d : [];
      } catch {
        users.value = [
          {
            id: "USR-001",
            user_code: "USR-001",
            username: "admin",
            plain_password: "admin12345",
            name: "Administrator",
            role: "admin",
            created_at: "2026-08-16",
          },
          {
            id: "USR-002",
            user_code: "USR-002",
            username: "manager01",
            plain_password: "pass12345",
            name: "Meeting Manager",
            role: "user",
            created_at: "2026-08-16",
          },
          {
            id: "USR-003",
            user_code: "USR-003",
            username: "iict",
            plain_password: "iict@2026",
            name: "ພະແນກຄຸ້ມຄອງ ແລະ ພັດທະນາ ໄອຊີທີ",
            role: "user",
            created_at: "2026-08-16",
          },
        ];
      }
    };
    const delUser = async (id) => {
      if (!confirm("ລຶບ?")) return;
      try {
        await api.delete(`/api/users/${id}`);
        users.value = users.value.filter((u) => u.id != id);
      } catch {
        users.value = users.value.filter((u) => u.id != id);
      }
    };
    onMounted(load);
    return { users, search, filtered, fmt, delUser };
  },
};
</script>
<style scoped>
.card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  padding: 20px;
}
.card-head {
  margin-bottom: 14px;
}
.text-muted {
  color: #94a3b8;
  font-size: 12px;
}
.search-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 8px 14px;
  border-radius: 20px;
  width: 320px;
  margin-bottom: 14px;
}
.search-wrap input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
  font-size: 13px;
}
.tbl {
  width: 100%;
  border-collapse: collapse;
}
.tbl th {
  font-size: 11px;
  color: #94a3b8;
  padding: 12px 8px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  text-transform: uppercase;
}
.tbl td {
  padding: 14px 8px;
  font-size: 13px;
  border-bottom: 1px solid #f8fafc;
}
.uid {
  color: #2563eb;
  font-weight: 700;
  font-size: 12px;
}
.fw-bold {
  font-weight: 700;
}
.role {
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
}
.role.admin {
  background: #ede9fe;
  color: #7c3aed;
}
.role.user {
  background: #eff6ff;
  color: #2563eb;
}
.icon-btn {
  border: none;
  background: transparent;
  width: 28px;
  height: 28px;
  border-radius: 8px;
}
.icon-btn.del {
  color: #e11d48;
}
</style>
