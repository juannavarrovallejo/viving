<template>
  <div v-if="home">
    <div class="row">
      <div class="col-12">
        <div class="d-flex align-items-center justify-content-center">
          <div class="input-group bg-white px-3 py-2 rounded-4 custom-search-panel">
            <div class="input-group-btn search-panel">
              <select
                style="width: 200px"
                v-model="form.doctor_category"
                class="border-0 py-3 me-3"
                aria-label="Select Category"
              >
                <option value selected>{{ __("select") }} {{ __("category") }}</option>
                <option v-for="cat in doctor_categories" :key="cat.id" :value="cat.slug">
                  {{ cat.name }}
                </option>
              </select>
            </div>
            <div>
              <select
                style="width: 200px"
                v-model="form.country"
                class="border-0 py-3"
                aria-label="Select Distance"
              >
                <option value selected>{{ __("select") }} {{ __("country") }}</option>
                <option
                  v-for="country in countries"
                  :key="country.id"
                  :value="country.id"
                >
                  {{ country.name }}
                </option>
              </select>
            </div>
            <div class="d-flex align-items-center" style="max-width: 400px">
              <!-- <span id="search_concept">
                    <i class="bi bi-search"></i>
                  </span> -->
              <input
                type="text"
                class="border-0 py-2 ms-3 shadow-none"
                v-model="form.search"
                id="findEventHome"
                :placeholder="__('search')"
              />
              <input
                type="text"
                class="border-0 py-2 ms-3 shadow-none"
                v-model="form.address"
                id="findEventHome"
                :placeholder="__('Address')"
              />
            </div>

            <span class="d-flex align-items-center">
              <button
                :href="route('events.listing')"
                @click="submit"
                class="btn btn-primary"
                type="submit"
              >
                <i class="bi bi-search"></i>
              </button>
              <!-- <button
                    :href="route('doctors.listing')"
                    @click="submit"
                    class="btn btn-primary text-white border-0 ms-3 py-2 px-4"
                    type="submit"
                  >
                    <i class="bi bi-search"></i>
                  </button> -->
            </span>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <input
          type="text"
          class="form-control border-0 py-3"
          v-model="form.search"
          id="findEventHome"
          :placeholder="__('search')"
        />
      </div>
      <!-- <div class="col-md-4">
            <input type="text" class="form-control border-0 py-3" v-model="form.address" id="findEventHome"
            :placeholder="__('address')">
        </div> -->

      <div class="col-md-4">
        <div class="d-flex">
          <select
            v-model="form.month"
            class="form-select border-0 py-3"
            aria-label="Select Distance"
          >
            <option value="" selected>{{ __("select") }} {{ __("month") }}</option>
            <option v-for="month in months" :key="month.id" :value="month.value">
              {{ month.name }}
            </option>
          </select>

          <button
            :href="route('events.listing')"
            @click="submit"
            class="btn btn-primary text-white border-0 ms-3 px-4"
            type="submit"
          >
            <i class="bi bi-search"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="row flex-column align-items-center">
    <div class="col-md-12 mb-3">
      <Label class="fs-2 text-black fw-bold mb-2">{{ __("Search By Months") }}</Label>
      <select v-model="form.month" class="form-select" aria-label="Select Distance">
        <option value="" selected>{{ __("select") }} {{ __("month") }}</option>
        <option
          class="text-black"
          v-for="month in months"
          :key="month.id"
          :value="month.value"
        >
          {{ month.name }}
        </option>
      </select>
    </div>

    <div class="col-md-12 mb-3">
      <Label class="fs-2 text-black fw-bold mb-2">{{
        __("Search By Address")
      }}</Label>
      <input
        type="text"
        v-model="form.address"
        class="form-control"
        id="findEventlisting2"
        :placeholder="__('address')"
      />
    </div>

    <div class="col-md-12 mb-3">
      <Label class="fs-2 text-black fw-bold mb-2">{{ __("Search By Name") }}</Label>
      <input
        type="text"
        v-model="form.search"
        class="form-control"
        id="findEventlisting"
        :placeholder="__('search')"
      />
    </div>

<div class="col-md-12">
    <div class="d-flex justify-content-center my-4 gap-3">



      <button
        @click="clearFilters()"
        type="button"
        class="fs-3 btn btn-primary fw-bold rounded-3 w-100 shadow-find"
        :disabled="isLoading"
      >
        <SpinnerLoader v-if="isLoading" />
        {{ __("clear all") }}
      </button>
      <button
        :href="route('events.listing')"
        @click="submit"
        class="fs-3 btn btn-secondary fw-bold rounded-3 w-100 shadow-find"
        type="submit"
        :disabled="isLoading"
      >
        <SpinnerLoader v-if="isLoading" />
        {{ __("search") }}
      </button>
    </div>
</div>



  </div>
</template>

<script>
import { defineComponent } from "vue";
import ValidationErrors from "@/Components/ValidationErrors.vue";
import SpinnerLoader from "@/Components/Shared/SpinnerLoader.vue";
import { Link } from "@inertiajs/inertia-vue3";
import axios from "axios";
import { router } from "@inertiajs/inertia-vue3";
export default defineComponent({
  components: {
    ValidationErrors,
    SpinnerLoader,
    Link,
  },
  props: {
    is_redirect: {
      type: Boolean,
      default: true,
    },
    home: {
      type: Boolean,
      default: false,
    },
  },
  created() {
    // this.getEventCategories()
    this.$emit("getEvents", this.form);
  },
  data() {
    return {
      isLoading: false,
      isClearLoading: false,
      form: {
        search: route().params.search ?? "",
        address: route().params.address ?? "",
        month: route().params.month ?? "",
      },
      months: [
        {
          id: 1,
          name: "Jan",
          value: "jan",
        },
        {
          id: 2,
          name: "Feb",
          value: "feb",
        },
        {
          id: 3,
          name: "Mar",
          value: "mar",
        },
        {
          id: 4,
          name: "Apr",
          value: "apr",
        },
        {
          id: 5,
          name: "May",
          value: "may",
        },
        {
          id: 6,
          name: "Jun",
          value: "jun",
        },
        {
          id: 7,
          name: "Jul",
          value: "jul",
        },
        {
          id: 8,
          name: "Aug",
          value: "aug",
        },
        {
          id: 9,
          name: "Sep",
          value: "sep",
        },
        {
          id: 10,
          name: "Oct",
          value: "oct",
        },
        {
          id: 11,
          name: "Nov",
          value: "nov",
        },
        {
          id: 12,
          name: "Dec",
          value: "dec",
        },
      ],
      //   event_categories: [],
    };
  },

  methods: {
    getEventCategories() {
      axios.get(this.route("getApiEventCategories")).then((res) => {
        this.event_categories = res.data.data;
      });
    },
    submit() {
      this.isLoading = true;
      const fetchDataPromise = new Promise((resolve, reject) => {
        setTimeout(() => {
          var payload = {
            data: this.form,
            replace: true,
            preserveScroll: true,
          };
          this.$inertia.replace(this.route("events.listing"), {
            data: this.form,
            replace: true,
            preserveScroll: true,
          });
          this.$emit("getEvents", this.form);
          resolve();
        }, 1000);
      });
      fetchDataPromise
        .then((data) => {})
        .catch((error) => {})
        .finally(() => {
          this.isLoading = false;
        });
    },

    clearFilters() {
      this.isClearLoading = true;
      const fetchDataPromise = new Promise((resolve, reject) => {
        setTimeout(() => {
          this.form.search = "";
          this.form.address = "";
          this.form.month = "";
          this.$inertia.replace(this.route("events.listing"));
          this.$emit("getEvents", this.form);
          resolve();
        }, 1000);
      });
      fetchDataPromise
        .then((data) => {})
        .catch((error) => {})
        .finally(() => {
          this.isClearLoading = false;
        });
    },
  },
});
</script>
