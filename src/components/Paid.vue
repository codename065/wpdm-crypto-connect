<template>
  <div class="w3eden" v-if="paid">
    <div class="card" style="margin: 10px auto;">
      <div class="card-body">
        <strong>Congratulation!</strong><br/>
        You already paid for this product. You access is valid until {{ paid.expiresAt }}.
      </div>
      <div class="card-footer">
        <a :href="paid.downloadURL" class="btn btn-primary">Download Now</a>
      </div>
    </div>
  </div>
</template>

<script type="module">
import { ref, onMounted } from 'vue';
import {wpdmCryptoConnect} from "@/data";

export default {
  props: ['product'],
  setup(props) {
    const product = ref(props.product || 0);
    const paid = ref(false);
    onMounted(() => {
      console.log('Calling paid...');
      jQuery.get(wpdm_url.ajax, {product: product.value, action: 'wpdm_crypto_paid'}, function (response) {
        console.log(response);
        paid.value = response.data;
      });
    });



    return {
      paid
    };
  }
};
</script>


