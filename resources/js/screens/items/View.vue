<template>
  <div>
    <Loader v-if="loader" />
    <div v-else class="screen-width">
      <div class="flex flex-wrap">
        <div class="w-full md:w-3/5 lg:w-2/3">
          <div class="h-64 w-full bg-cover rounded-md md:bg-contain lg:bg-top lg:h-full bg-no-repeat bg-center" :style="{ backgroundImage: `url(${item.image ? '/storage/' + item.image : '/assets/images/cover.png'})` }"></div>
        </div>
        <div class="w-full md:w-2/5 lg:w-1/3 px-6 py-10">
          <h3 class="font-bold">{{ item.name }}</h3>
          <span class="block mb-4">Tawaran Terendah IDR {{ formatRupiah(item.min_bid) }}</span>
          
          <p class="mb-6 font-thin leading-relaxed text-sm">
            <span class="block font-semibold">Details</span>
            <span>{{ itemDetails(item.details) }}</span>
            <span v-if="item.details.length > 500">
              <span v-if="hasReadMore">... </span>
              <span class="text-purple-400 cursor-pointer ml-1 text-xs">
                <span v-if="hasReadMore" @click="hasReadMore = false">
                  Baca selengkapnya
                </span>
                <span v-else @click="hasReadMore = true">Lihat lebih sedikit</span>
              </span>
            </span>
          </p>

          <div class="flex flex-col space-y-4 mb-2">
            <div>
              <span class="block font-semibold">Tersedia Sampai</span>
              <span class="block">{{ formatDataTime() }}</span>
            </div>
            <div>
              <span class="block font-semibold">Tawaran Terakhir</span>
              <span class="block">IDR {{ item.max_bid != null ? formatRupiah(item.max_bid) : formatRupiah(item.min_bid) }}</span>
            </div>
          </div>

          <!-- bid now -->
          <div>
            <div v-if="isLoggedIn && isOwner">
              <div class="p-4 bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500">
                <p class="font-bold">Peringatan</p>
                <p>Anda tidak dapat melakukan bid pada lelang yang Anda buat sendiri.</p>
              </div>
            </div>

            <div v-else-if="isLoggedIn && !isClosed">
              <div v-if="!item.hasAutoBid">
                <CustomForm requestType="put" :url="`/api/items/${id}`" saveBtnText="Ajukan Tawaran" saveBtnClass="w-full btn btn-primary mb-2">
                  <template #content="{fields}">
                    <FormInput isRequired iconText="IDR" type="number" v-model="fields.max_bid" :bindOptions="{min: +item.max_bid + 1}" :placeholder="`Tawar sekarang dengan lebih dari IDR ${formatRupiah(item.max_bid)}`" />
                  </template>
                </CustomForm>
              </div>
            </div>

            <div v-else-if="isLoggedIn && isClosed">
              <div class="p-4 bg-orange-100 text-orange-700 border-l-4 border-orange-500">
                <p class="font-bold">Peringatan</p>
                <p>Item ini sudah ditutup, anda tidak bisa mengajukan penawaran</p>
              </div>
            </div>

            <div v-else>
              <div class="p-4 bg-orange-100 text-orange-700 border-l-4 border-orange-500">
                <p class="font-bold">Peringatan</p>
                <p>
                  Anda harus
                  <router-link to="/login" class="font-bold">login</router-link>
                  terlebih dahulu untuk bisa mengajukan tawaran
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters} from "vuex"
import moment, {now} from "moment"
import Modal from "./../../components/Modal.vue"
import CustomBtn from "../../components/CustomBtn.vue"
import FormInput from "./../../components/FormInput.vue"
import CustomForm from "./../../components/CustomForm.vue"

export default {
  components: {CustomForm, FormInput, Modal, CustomBtn},
  data() {
    return {
      item: {},
      loader: true,
      hasReadMore: true,

      isClosed: false,
      stopBtnLoading: false,

      autoBidObj: {},
      autoBidBtn: false,
      hasAutoBid: false,
      autoBiddingModal: false,
    }
  },

  computed: {
    ...mapGetters(["isLoggedIn", "user"]),

    id() {
      return this.$route.params.id
    },

    isOwner() {
      const userStr = localStorage.getItem("user");
      const user = userStr ? JSON.parse(userStr) : null;
      const userId = user ? user.id : null;
      console.log(userId)
      return this.isLoggedIn && this.item.create_by_user_id === userId;
    },
  },

  methods: {
    formatRupiah(value) {
      if (!value) return '0'
      return new Intl.NumberFormat('id-ID').format(value)
    },

    getItmeData() {
      this.axios.get(`/api/items/${this.id}`).then(({data}) => {
        this.item = data
        this.loader = false
      })
    },

    formatDataTime() {
      let atThisMoment = moment(now())
      let until = moment(this.item.available_untill)
      if (atThisMoment.isBefore(until)) {
        let {_data} = moment.duration(until.diff(atThisMoment))
        let time = ""

        //years Format
        if (_data.years) {
          if (_data.years != 1) {
            time += `${_data.years} Tahun, `
          } else {
            time += `Tahun, `
          }
        }

        //months Format
        if (_data.months) {
          if (_data.months != 1) {
            time += `${_data.months} bulan, `
          } else {
            time += `Bulan, `
          }
        }

        //years Format
        if (_data.days) {
          if (_data.days != 1) {
            time += `${_data.days} Hari, `
          } else {
            time += `Hari, `
          }
        }

        //hours Format
        if (_data.hours) {
          if (_data.hours < 10) {
            time += `0${_data.hours}:`
          } else {
            time += `${_data.hours}:`
          }
        } else {
          time += `00:`
        }

        //minutes Format
        if (_data.minutes) {
          if (_data.minutes < 10) {
            time += `0${_data.minutes}:`
          } else {
            time += `${_data.minutes}:`
          }
        } else {
          time += `00:`
        }

        //seconds Format
        if (_data.seconds) {
          if (_data.seconds < 10) {
            time += `0${_data.seconds}`
          } else {
            time += `${_data.seconds}`
          }
        } else {
          time += `00`
        }

        return time
      } else {
        this.isClosed = true
        return "Penawaran ini telah ditutup"
      }
    },

    itemDetails(desc) {
      if (desc.length > 500) {
        if (this.hasReadMore) {
          return desc.slice(500)
        }
        return desc
      } else {
        return desc
      }
    },
  },

  created() {
    this.getItmeData()
  },

  mounted() {
    setInterval(() => {
      this.$forceUpdate(() => this.formatDataTime())
    }, 1000)

    this.$echo
      .private(`item-has-bid-${this.$store.state.user.id}`)
      .listen("ItemWithBidsEvent", ({itemWithBid}) => {
        this.hasAutoBid = true
        this.$set(this, "item", itemWithBid)
      })

    this.$echo.channel("update-item").listen("ItemEvent", ({item}) => {
      if (this.hasAutoBid) {
        this.hasAutoBid = false
        return
      }

      if (this.id == item.id) {
        this.$set(this, "item", item)
        this.$emit("clear-errors")
      }
    })
  },
}
</script>
