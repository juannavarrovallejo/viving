<template>
  <div class="tab-pane" :class="{ active: active }" id="calendly" role="tabpanel" aria-labelledby="calendly-tab"
    tabindex="0">
    <form @submit.prevent="submit" class="profileForm">
      <div class="row">
        <div class="col-md-12">
          <validation-errors></validation-errors>
          <div class="col-12">
            <div class="row">
              <div v-for="(setting, i) in form.settings" :key="i" class="col-md-6">
                <div  class="form-group mb-3">
                  <label :for="setting.display_name">{{setting.display_name}}</label>
                  <input v-model="setting.value" placeholder="Please Enter" class="w-100 form-control  px-3"
                    type="text" />
                </div>

              </div>

            </div>
            <div class="row align-items-center">
              <div class="col-md-2">
                <button type="submit"  :disabled="form.processing" class="submit btn btn-primary">
                  <SpinnerLoader v-if="form.processing" />
                  {{__('save')}}
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </form>
  </div>
</template>
<script>
import { defineComponent } from "vue";
import ValidationErrors from "@/Components/ValidationErrors.vue";
import SpinnerLoader from "@/Components/Shared/SpinnerLoader.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import axios from "axios";

export default defineComponent({
  components: {
    Head,
    SpinnerLoader,
    ValidationErrors,
    Link,
  },
  props: ['active'],
  data() {
    return {
      form: this.$inertia.form({
        settings: [{
          'name': 'calendly_link',
          'display_name': "Calendly Link",
          'value': this.$page.props.clinic.clinic_settings['calendly_link'] ?? "",
        },
        ]
      }),
    };
  },
  methods: {
    submit() {
      this.form
        .transform((data) => ({
          ...data,
          remember: this.form.remember ? "on" : "",
        }))
        .post(this.route("clinics.update_settings"), {
            onSuccess: () => {
                        //  this.goToNextTab()
                    }
        });
    },
    goToNextTab(){
            this.$inertia.visit(route('account'),{data:{active_tab:'broadcasts'}})
        }
  },
});
</script>
