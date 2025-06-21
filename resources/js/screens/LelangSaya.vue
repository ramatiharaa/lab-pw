<template>

  <div class="p-4">
    <div v-if="editModalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow w-full max-w-lg relative">
            <h2 class="text-lg font-bold mb-4">Edit Lelang</h2>
            <form @submit.prevent="submitEditLelang">
                <input v-model="editForm.name" type="text" placeholder="Nama" class="input" required />
                <textarea v-model="editForm.details" placeholder="Deskripsi" class="input" required></textarea>
                <input v-model.number="editForm.min_bid" type="number" placeholder="Bid Minimum" class="input" required />
                <p class="text-sm text-gray-500">IDR {{ formatRupiah(editForm.min_bid) }}</p>
                <input v-model="editForm.available_untill" type="datetime-local" class="input" required />
                <input type="file" @change="handleEditImageUpload" class="input" />
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="btn bg-gray-500" @click="closeEditModal">Batal</button>
                    <button type="submit" class="btn">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div v-else class="screen-width">
      <h1 class="text-2xl font-bold mb-4">Lelang Saya</h1>
      <!-- Form Tambah Lelang -->
      <div class="mb-6 border p-4 rounded bg-gray-50">
        <h2 class="font-semibold mb-2">Tambah Lelang</h2>
        <form @submit.prevent="submitLelang">
          <input v-model="form.name" type="text" placeholder="Nama" class="input" required />
          <textarea v-model="form.details" placeholder="Deskripsi" class="input" required></textarea>
          <input v-model.number="form.min_bid" type="number" placeholder="Bid Minimum" class="input" required />
          <p class="text-sm text-gray-500">IDR {{ formatRupiah(form.min_bid) }}</p>
          <input v-model="form.available_untill" type="datetime-local" class="input" required />
          <input type="file" @change="handleImageUpload" class="input" />
          <button type="submit" class="btn">Simpan</button>
        </form>
      </div>

      <!-- List Lelang -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="lelang in lelangs" :key="lelang.id" class="w-full card overflow-hidden flex flex-col justify-center">
          <div class="bg w-full h-64 flex justify-center">
            <div class="w-full h-full bg-cover bg-no-repeat bg-center" :style="{ backgroundImage: `url(${lelang.image ? '/storage/' + lelang.image : '/assets/images/cover.png'})` }"></div>
          </div>
          <div class="p-2 flex space-y-2 flex-col">
            <h4 class="truncate">{{ lelang.name }}</h4>
            <div class="flex flex-wrap items-center justify-between">
              <span>IDR {{ formatRupiah(lelang.min_bid) }}</span>
              <button @click="openEditModal(lelang)" class="text-blue-500 hover:underline mr-2">Edit</button> 
              <button @click="hapusLelang(lelang.id)" class="text-red-500 hover:underline">Hapus</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
        lelangs: [],
        loading: false,
        form: {
            name: '',
            details: '',
            min_bid: '',
            max_bid: '',
            available_untill: '',
            image: '',
        },
        editModalVisible: false,
        editForm: {
            id: null,
            name: '',
            details: '',
            min_bid: '',
            max_bid: '',
            available_untill: '',
            image: '',
        },
    };
  },

  methods: {
    formatRupiah(value) {
      if (!value) return '0'
      return new Intl.NumberFormat('id-ID').format(value)
    },

    async submitEditLelang() {
    try {
        const formData = new FormData();
        formData.append('name', this.editForm.name);
        formData.append('details', this.editForm.details);
        formData.append('min_bid', this.editForm.min_bid);
        formData.append('max_bid', this.editForm.max_bid);
        formData.append('available_untill', this.editForm.available_untill);
        if (this.editForm.image) {
        formData.append('image', this.editForm.image);
        }
        formData.append('_method', 'PUT'); // Laravel method spoofing

        await this.axios.post(`/api/itemsupdate/${this.editForm.id}`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
        });
        this.fetchLelangSaya();
        this.closeEditModal();
    } catch (e) {
        console.error("Gagal update:", e.response?.data || e.message);
    }
    },


    openEditModal(lelang) {
        this.editForm = { ...lelang, image: '' }; // reset image upload
        this.editModalVisible = true;
        },
        closeEditModal() {
        this.editModalVisible = false;
        },
        handleEditImageUpload(event) {
        const file = event.target.files[0];
        this.editForm.image = file;
        },


    handleImageUpload(event) {
        const file = event.target.files[0];
        this.form.image = file;
    },

    async fetchLelangSaya() {
        this.loader = true
        this.axios.get(`/api/itemsbycreateid`).then(({data}) => {
            this.lelangs = data
            this.loader = false
      })
    },

    async submitLelang() {
        try {
            this.loading = true;

            const formData = new FormData();
            formData.append('name', this.form.name);
            formData.append('details', this.form.details);
            formData.append('min_bid', this.form.min_bid);
            formData.append('available_untill', this.form.available_untill);
            if (this.form.image) {
                formData.append('image', this.form.image);
            }

            const res = await this.axios.post('/api/items', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
            });

            console.log(res);

            this.fetchLelangSaya();
            this.resetForm();
        } catch (e) {
            console.error("Gagal tambah lelang:", e.response?.data || e.message);
        } finally {
            this.loading = false;
        }
    },

    async hapusLelang(id) {
      if (confirm('Yakin ingin menghapus lelang ini?')) {
        try {
          await this.axios.delete(`/api/items/${id}`);
          this.fetchLelangSaya();
        } catch (e) {
          console.error(e);
        }
      }
    },
    resetForm() {
      this.form = {
        name: '',
        details: '',
        min_bid: '',
        max_bid: '',
        available_untill: '',
        image: '',
      };
    },
  },
  mounted() {
    this.fetchLelangSaya();
  },
};
</script>

<style scoped>
.input {
  display: block;
  margin-bottom: 10px;
  padding: 8px;
  width: 100%;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.btn {
  background-color: #3b82f6;
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  border: none;
}
</style>
