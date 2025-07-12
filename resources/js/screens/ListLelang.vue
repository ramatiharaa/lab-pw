<template>
  <div>
    <Loader v-if="loader" />
    <div v-else class="screen-width">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          :key="index"
          v-for="(item, index) in items"
          class="w-full card overflow-hidden flex flex-col justify-center"
        >
          <div class="bg w-full h-64 flex justify-center">
            <div
              class="w-full h-full bg-cover bg-no-repeat bg-center"
              :style="{ backgroundImage: `url(${item.image ? '/storage/' + item.image : '/assets/images/cover.png'})` }"
            ></div>
          </div>
          <div class="p-2 flex space-y-2 flex-col">
            <h4 class="truncate">{{ item.name }}</h4>
            <div class="flex flex-wrap items-center justify-between">
              <span>
                IDR {{
                  item.max_bid != null
                    ? formatRupiah(item.max_bid)
                    : formatRupiah(item.min_bid)
                }}
              </span>
              <router-link
                :to="`/items/${item.id}`"
                class="transition-all border border-purple-600 hover:text-white hover:bg-purple-700 rounded text-xs p-1 px-2"
              >
                Detail
              </router-link>
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
      items: [],
      loader: true,
    };
  },

  methods: {
    getItems() {
      this.axios
        .get("/api/my-items")
        .then(({ data }) => {
          this.items = data.my_items;
          this.loader = false;
        })
        .catch((error) => {
          console.error("Gagal mengambil data:", error);
          this.loader = false;
        });
    },

    formatRupiah(value) {
      if (!value) return "0";
      return new Intl.NumberFormat("id-ID").format(value);
    },
  },

  created() {
    this.getItems();
  },

  mounted() {
    this.$echo.channel("update-item").listen("ItemEvent", (payload) => {
      this.items = this.items.map((item) => {
        return item.id === payload.item.id ? payload.item : item;
      });
    });
  },
};
</script>
