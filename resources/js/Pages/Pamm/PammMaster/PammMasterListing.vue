<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {computed, ref, watch} from "vue";
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import JoinPammForm from "@/Pages/Pamm/PammMaster/JoinPammForm.vue";
import NoData from "@/Components/NoData.vue";
import PammMasterListView from "@/Pages/Pamm/PammMaster/PammMasterListView.vue";
import MasterListView from "@/Pages/CopyTrading/MasterListView.vue";

const props = defineProps({
    mastersCount: Number,
    strategyType: String,
    pammType: String,
})
</script>

<template>
    <AuthenticatedLayout :title="$t(`public.${strategyType}_pamm_trading`)">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold">
                    {{ $t(`public.${strategyType}_pamm_trading`) }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Master List -->
            <PammMasterListView
                :mastersCount="mastersCount"
                :strategy="strategyType"
                :pammType="pammType"
            />
        </div>
    </AuthenticatedLayout>
</template>
