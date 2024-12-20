<script setup>
import Button from "primevue/button";
import {
    IconCirclePlus,
    IconCircleCheckFilled
} from "@tabler/icons-vue";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import RadioButton from "primevue/radiobutton";
import Select from "primevue/select";
import InputLabel from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "primevue/checkbox";

const props = defineProps({
    activeAccountCounts: Number,
    liveAccountQuota: Number,
    enableVirtualAccount: Boolean,
})

const visible = ref(false);
const form = useForm({
    leverage: '',
    terms: '',
    account_type: '',
})

const openDialog = () => {
    visible.value = true;
    getAccountTypes();
    getLeverages();
}

const selectedAccountType = ref('hofi');
const selectAccount = (type) => {
    selectedAccountType.value = type.slug;
    getLeverages();
}

const loadingAccountTypes = ref(false);
const accountTypes = ref();

const getAccountTypes = async () => {
    loadingAccountTypes.value = true;
    try {
        const response = await axios.get('/getAccountTypes');
        accountTypes.value = props.enableVirtualAccount
            ? response.data
            : response.data.filter(account => account.slug !== 'virtual');
    } catch (error) {
        console.error('Error fetching account types:', error);
    } finally {
        loadingAccountTypes.value = false;
    }
};

const loadingLeverages = ref(false);
const leverages = ref();
const selectedLeverage = ref();

const getLeverages = async () => {
    loadingLeverages.value = true;
    try {
        const response = await axios.get(`/getLeverages?account_type=${selectedAccountType.value}`);
        leverages.value = response.data;
        selectedLeverage.value = leverages.value[0].value;
    } catch (error) {
        console.error('Error fetching leverages:', error);
    } finally {
        loadingLeverages.value = false;
    }
};

const submitForm = () => {
    form.account_type = selectedAccountType.value;
    form.leverage = selectedLeverage.value;
    form.post(route('account_info.createAccount'), {
        onSuccess: () => {
            closeDialog()
            form.reset()
        },
        onError: (errors) => {
            console.error('Submission errors:', errors)
        }
    })
}

const closeDialog = () => {
    visible.value = false
}
</script>

<template>
    <Button
        type="button"
        class="flex justify-center items-center gap-2 w-full sm:w-auto"
        :disabled="activeAccountCounts >= liveAccountQuota && !enableVirtualAccount"
        size="small"
        @click="openDialog"
    >
        <IconCirclePlus size="20" />
        <span>{{ $t('public.add_account') }}</span>
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.add_account')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col items-center gap-8 self-stretch">
                <div class="flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col items-start gap-2 self-stretch">
                        <InputLabel for="accountType" :value="$t('public.type')" />
                        <div
                            v-if="loadingAccountTypes"
                            class="grid grid-cols-1 md:grid-cols-2 items-start gap-3 self-stretch"
                        >
                            <div
                                v-for="account in 2"
                                class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full bg-primary-50 dark:bg-gray-800 border-primary-500"
                            >
                                <span
                                    class="flex-grow text-sm font-semibold text-gray-950 dark:text-white"
                                >
                                    {{ $t('public.loading') }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 items-start gap-3 self-stretch"
                        >
                            <div
                                v-for="account in accountTypes"
                                @click="selectAccount(account)"
                                class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                                :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedAccountType === account.slug,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedAccountType !== account.slug,
                                }"
                            >
                                <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedAccountType === account.slug,
                                        'text-gray-950 dark:text-white': selectedAccountType !== account.slug
                                    }"
                                >
                                    {{ $t(`public.${account.slug}`) }}
                                </span>
                                    <IconCircleCheckFilled v-if="selectedAccountType === account.slug" size="20" stroke-width="1.25" color="#2970FF" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="leverage"
                            :value="$t('public.leverage')"
                            :invalid="!!form.errors.leverage"
                        />
                        <Select
                            input-id="leverage"
                            v-model="selectedLeverage"
                            :options="leverages"
                            optionLabel="label"
                            optionValue="value"
                            :placeholder="$t('public.select_leverage')"
                            class="w-full"
                            :loading="loadingLeverages"
                            :invalid="!!form.errors.leverage"
                        />
                        <InputError :message="form.errors.leverage" />
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{
                            $t('public.terms_and_conditions')
                        }}</h3>
                    <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                        <li>{{ $t('public.terms_1') }}</li>
                        <li>{{ $t('public.terms_2') }}</li>
                        <li>{{ $t('public.terms_3') }}</li>
                        <li>{{ $t('public.terms_4') }}</li>
                        <li>{{ $t('public.terms_5') }}</li>
                        <li>{{ $t('public.terms_6') }}</li>
                        <li>{{ $t('public.terms_7') }}</li>
                        <li v-if="enableVirtualAccount">{{ $t('public.terms_8') }}</li>
                        <li>{{ $t('public.terms_9') }}</li>
                    </ol>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <div class="flex items-start gap-2 self-stretch w-full">
                            <Checkbox
                                v-model="form.terms"
                                inputId="terms"
                                binary
                                :invalid="!!form.errors.terms"
                            />
                            <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{
                                    $t('public.accept_terms')
                                }}</label>
                        </div>
                        <InputError :message="form.errors.terms"/>
                    </div>
                </div>
            </div>

            <div class="flex w-full justify-end gap-3">
                <Button
                    type="button"
                    severity="secondary"
                    text
                    class="justify-center w-full md:w-auto px-6"
                    @click="closeDialog"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="submit"
                    class="justify-center w-full md:w-auto px-6"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
