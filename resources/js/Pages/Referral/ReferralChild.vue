<script>
import {PlusIcon, MinusSmIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {useLangObserver} from "@/Composables/localeObserver.js";

export default {
    name: 'Tree',
    components: { PlusIcon, MinusSmIcon, ref, Modal},
    props: {
        node: Object,
        depth: {
            type: Number,
            default: 0,
        },
        isLoading: Boolean,
    },
    data() {
        return {
            expanded: false,
        }
    },
    methods: {
        nodeClicked() {
            this.expanded = !this.expanded
            if (!this.hasChildren) {
                this.$emit('onClick', this.node)
            }
        },
        openAffiliateModal(node) {
            this.selectedAffiliate = node;
            this.affiliateModal = true;
        },
        closeModal() {
            this.affiliateModal = false;
        }
    },
    computed: {
        hasChildren() {
            const node = this.node;

            if (node.children && node.children.length > 0) {
                for (const child of node.children) {
                    if (child.id === node.id) {
                        return false;
                    }
                }
            }

            return this.node.children;
        },
        // iconSizeClasses() {
        //     return this.node.role === 'ib' ? 'text-[#FF9E23]' : 'text-[#007BFF]';
        // },
        // bgColorClass() {
        //     return this.node.role === 'ib' ? 'bg-[#FF9E23] text-dark-eval-2' : 'bg-[#007BFF] text-dark-eval-2';
        // },
    },
    setup() {
        function formatAmount(amount) {
            return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        const affiliateModal = ref(false);
        const selectedAffiliate = ref();
        const {locale} = useLangObserver();
        return {
            formatAmount,
            affiliateModal,
            selectedAffiliate,
            locale
        };
    },
    emits: ['onClick']
}

</script>

<template>
    <div>
        <div :style="{'margin-left': `${depth * 50}px`}">
            <div class="flex items-center mb-6 gap-2">
                <div class="flex-none">
                    <div v-if="hasChildren && node.children.length !== 0">
                        <template v-if="expanded">
                            <!-- Show the MinusSmIcon if expanded -->
                            <div class="w-5 h-5 rounded-full shrink-0 grow-0 cursor-pointer hover:bg-gray-500 dark:hover:bg-gray-600 bg-gray-400 dark:bg-gray-700 flex items-center justify-center"
                                @click="nodeClicked">
                                <MinusSmIcon aria-hidden="true" :class="['w-4 h-4 text-white']" />
                            </div>
                        </template>

                        <template v-else>
                            <!-- Show the PlusIcon if not expanded -->
                            <div v-if="hasChildren"
                                class="w-5 h-5 rounded-full shrink-0 grow-0 cursor-pointer hover:bg-primary-600 bg-primary-500 flex items-center justify-center"
                                @click="nodeClicked">
                                <PlusIcon aria-hidden="true" :class="['w-4 h-4 text-white']" />
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex">
                    <div class="flex items-center p-2.5 text-sm text-gray-800 border border-gray-300 shadow-lg dark:border-transparent rounded-lg bg-gray-50 dark:bg-gray-900 hover:cursor-pointer hover:border-primary-500 dark:hover:bg-gray-800 overflow-x-auto"
                        @click="openAffiliateModal(node)">
                        <div role="status" class="animate-pulse" v-if="isLoading">
                            <div class="flex items-center space-x-3">
                                <svg class="w-10 h-10 text-gray-200 dark:text-gray-600" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z" />
                                </svg>
                                <div>
                                    <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-600 w-32 mb-2"></div>
                                    <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-600"></div>
                                </div>
                            </div>
                            <span class="sr-only">{{$t('public.is_loading')}}</span>
                        </div>

                        <div v-else
                            class="flex items-center space-x-4 text-base font-bold text-gray-900 dark:text-white w-80 md:w-56">
                            <img class="object-cover w-10 h-10 rounded-full"
                                :src="node.profile_photo ? node.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                alt="userPic" />
                            <div class="flex-col ml-3">
                                <div class="flex whitespace-nowrap gap-2 text-sm font-semibold items-center">
                                    <div class="whitespace-normal">{{ node.username }}</div>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full text-primary-100 bg-primary-400 dark:bg-primary-600">{{$t('public.level')}}
                                        {{ node.level }}</span>
                                </div>
                                <div class="text-xs font-normal dark:text-gray-400">
                                    {{$t('public.rank')}}: {{ locale === 'cn' ? node.rank[locale] : node.rank['en'] }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="inline-block h-auto min-h-[3em] w-0.5 self-stretch bg-dark-eval-4 dark:bg-gray-600 opacity-100 mx-3 my-1">
                        </div>
                        <div role="status" class="animate-pulse" v-if="isLoading">
                            <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-600 w-48 my-2"></div>
                            <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-600 mb-2.5"></div>
                            <span class="sr-only">{{$t('public.is_loading')}}</span>
                        </div>
                        <div v-else class="flex items-center w-full md:w-auto gap-3 text-lg dark:text-white">
                            <div class="flex flex-col text-center">
                                <span class="text-sm font-semibold">{{ node.direct_affiliate ? node.direct_affiliate : 0
                                    }}</span>
                                <span
                                    class="text-xs font-normal dark:text-gray-400">{{$t('public.direct_clients')}}</span>
                            </div>
                            <div class="flex flex-col text-center">
                                <span class="text-sm font-semibold">{{ node.total_affiliate }}</span>
                                <span
                                    class="text-xs font-normal dark:text-gray-400">{{$t('public.total_clients')}}</span>
                            </div>
                            <div class="flex flex-col text-center">
                                <span class="text-sm font-semibold">$ {{ formatAmount(node.self_deposit) }}</span>
                                <span
                                    class="text-xs font-normal dark:text-gray-400">{{$t('public.total_deposit')}}</span>
                            </div>
                            <div class="flex flex-col text-center">
                                <span class="text-sm font-semibold">$ {{ formatAmount(node.total_group_deposit)
                                    }}</span>
                                <span
                                    class="text-xs font-normal dark:text-gray-400">{{$t('public.total_group_deposit')}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Tree v-if="expanded && !isLoading" v-for="child in node.children" :key="child.id" :node="child"
            :depth="depth + 1" @onClick="(node) => $emit('onClick', node)" />
        <Modal :show="affiliateModal" :title="$t('public.view_details')" @close="closeModal">
            <div v-if="selectedAffiliate">
                <div
                    class="flex items-center p-2.5 mb-3 text-sm text-gray-800 rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    <img class="object-cover w-10 h-10 rounded-full"
                        :src="selectedAffiliate.profile_photo ? selectedAffiliate.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                        alt="userPic" />
                    <div class="flex-col ml-3">
                        <div class="flex whitespace-nowrap gap-2 text-sm font-semibold items-center">
                            <div class="whitespace-normal">{{ selectedAffiliate.username }}</div>
                            <span class="text-xs px-2 py-0.5 rounded-full text-primary-100 bg-primary-400 dark:bg-primary-600">{{ $t('public.level') }} {{ selectedAffiliate.level }}</span>
                        </div>
                        <div class="text-xs font-normal dark:text-gray-400"> {{ $t('public.rank') }}: {{ locale === 'cn' ? selectedAffiliate.rank[locale] : selectedAffiliate.rank['en'] }} </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 items-center">
                    <span
                        class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.direct_clients') }}</span>
                    <span class="text-black dark:text-white py-2">{{ selectedAffiliate.direct_affiliate ? selectedAffiliate.direct_affiliate : 0 }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 items-center">
                    <span
                        class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.total_clients') }}</span>
                    <span class="text-black dark:text-white py-2">{{ selectedAffiliate.total_affiliate }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 items-center">
                    <span
                        class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.total_deposit') }}</span>
                    <span class="text-black dark:text-white py-2">$ {{ formatAmount(selectedAffiliate.self_deposit)
                        }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 items-center">
                    <span
                        class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.total_group_deposit')}}</span>
                    <span class="text-black dark:text-white py-2">$ {{
                        formatAmount(selectedAffiliate.total_group_deposit) }}</span>
                </div>
            </div>
        </Modal>
    </div>

</template>
