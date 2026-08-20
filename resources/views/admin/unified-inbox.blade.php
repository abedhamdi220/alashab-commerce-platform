@extends('layouts.app')

@section('content')
<div
    x-data="unifiedInbox()"
    x-init="init()"
    class="flex h-screen bg-slate-50 font-sans overflow-hidden"
    dir="rtl"
>
    <!-- القائمة الجانبية للمحادثات -->
    <div class="w-full md:w-1/3 lg:w-96 bg-white border-l border-slate-200 flex flex-col shadow-xl z-20">
        <div class="p-6 bg-slate-900 text-white">
            <h2 class="text-xl font-bold flex items-center gap-3">
                <span class="p-2 bg-teal-500/20 rounded-lg">
                    <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </span>
                البريد الموحد
            </h2>
            <div class="mt-4 relative">
                <input type="text" placeholder="ابحث عن عميل أو رقم هاتف..." class="w-full bg-slate-800 border-none rounded-xl py-2.5 px-4 text-sm text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-teal-500 transition-all" />
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <!-- استخدام template للحلقات التكرارية مع Alpine -->
            <template x-for="(msg, index) in chatList" :key="msg.customer.id">
                <div
                    @click="selectCustomer(msg.customer)"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-x-5"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    :class="selectedCustomer?.id === msg.customer.id ? 'bg-teal-50' : 'hover:bg-slate-50'"
                    class="p-4 border-b border-slate-100 cursor-pointer transition-all duration-200 relative group"
                >
                    <!-- المؤشر الجانبي للمحادثة النشطة -->
                    <template x-if="selectedCustomer?.id === msg.customer.id">
                        <div class="absolute right-0 top-0 bottom-0 w-1 bg-teal-500 rounded-l-md"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"></div>
                    </template>

                    <div class="flex justify-between items-start mb-1.5">
                        <h3
                            :class="selectedCustomer?.id === msg.customer.id ? 'text-teal-900' : 'text-slate-800'"
                            class="font-bold text-sm truncate"
                            x-text="msg.customer?.name || 'عميل غير مسجل'"
                        ></h3>
                        <span
                            :class="msg.customer?.platform === 'whatsapp' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
                            class="text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1 shadow-sm shrink-0"
                            x-text="msg.customer?.platform === 'whatsapp' ? 'واتساب' : 'ماسنجر'"
                        ></span>
                    </div>
                    <div class="flex justify-between items-end">
                        <p class="text-xs text-slate-500 truncate pr-1 max-w-[80%]">
                            <template x-if="msg.direction === 'outbound'">
                                <span class="text-teal-600 font-medium">أنت: </span>
                            </template>
                            <span x-text="msg.message_type === 'media' ? '📷 مرفق' : msg.body"></span>
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- منطقة الدردشة -->
    <div class="flex-1 flex flex-col relative bg-slate-50/50 hidden md:flex">

        <!-- حالة وجود عميل محدد -->
        <template x-if="selectedCustomer">
            <div
                class="flex flex-col h-full w-full"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            >
                <!-- ترويسة المحادثة وإجراءات الطلب -->
                <div class="bg-white px-6 py-4 border-b border-slate-200 flex justify-between items-center shadow-sm z-10">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-xl font-bold text-white shadow-md"
                             x-text="selectedCustomer?.name?.charAt(0) || '👤'">
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800" x-text="selectedCustomer?.name"></h2>
                            <p class="text-sm text-slate-500 font-mono tracking-wider" dir="ltr" x-text="selectedCustomer?.phone_number"></p>
                        </div>
                    </div>

                    <button
                        x-show="!selectedCustomer?.active_order_id"
                        @click="handleCreateOrder(selectedCustomer)"
                        :disabled="isOrderLoading"
                        class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-teal-700 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        إنشاء مسودة طلب
                    </button>
                </div>

                <!-- مسودة الطلب: الأسعار والإجمالي يعيدان من الخادم فقط -->
                <template x-if="selectedOrder">
                    <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 space-y-3">
                        <div class="flex flex-wrap justify-between gap-3 items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-500">طلب رقم</span>
                                <span class="text-xs font-mono text-slate-700" x-text="`#${selectedOrder.id}`"></span>
                                <span class="text-xs px-2 py-1 rounded-md bg-teal-100 text-teal-700 font-bold" x-text="orderStatusLabel(selectedOrder.status)"></span>
                            </div>
                            <p class="font-bold text-slate-800">
                                الإجمالي: <span x-text="Number(selectedOrder.total_price || 0).toFixed(2)"></span>
                            </p>
                        </div>

                        <template x-if="selectedOrder.status === 'new'">
                            <div class="bg-white border border-slate-200 rounded-xl p-3 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-[1fr_110px_auto] gap-2">
                                    <select x-model="selectedProductId" class="rounded-lg border-slate-300 text-sm">
                                        <option value="">اختر منتجاً لإضافته</option>
                                        <template x-for="product in merchantProducts" :key="product.id">
                                            <option :value="product.id" x-text="`${product.name} — ${Number(product.price).toFixed(2)}`"></option>
                                        </template>
                                    </select>
                                    <input x-model.number="selectedQuantity" type="number" min="1" class="rounded-lg border-slate-300 text-sm" aria-label="الكمية" />
                                    <button @click="addOrderItem()" :disabled="isOrderLoading || !selectedProductId" class="bg-teal-600 text-white px-3 py-2 rounded-lg text-sm font-bold disabled:opacity-50">إضافة</button>
                                </div>

                                <template x-if="!selectedOrder.items || selectedOrder.items.length === 0">
                                    <p class="text-sm text-amber-700 bg-amber-50 p-2 rounded-lg">أضف منتجاً واحداً على الأقل قبل تأكيد الطلب.</p>
                                </template>
                                <template x-for="item in selectedOrder.items || []" :key="item.id">
                                    <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                                        <span x-text="`${item.product?.name || 'منتج'} × ${item.quantity}`"></span>
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono" x-text="Number(item.total_price).toFixed(2)"></span>
                                            <button @click="removeOrderItem(item.id)" class="text-rose-600 font-bold text-xs">حذف</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-xs font-bold text-slate-500">الإجراء التالي:</span>
                            <template x-for="tag in availableTransitions" :key="tag">
                                <button
                                    @click="handleUpdateTag(tag)"
                                    :disabled="isOrderLoading"
                                    class="bg-white border border-slate-200 hover:border-teal-500 hover:bg-teal-50 text-slate-600 hover:text-teal-700 px-3 py-2 rounded-lg text-xs font-bold transition-all shadow-sm disabled:opacity-50"
                                    x-text="orderStatusLabel(tag)"
                                ></button>
                            </template>
                        </div>

                        <p x-show="orderError" class="text-sm text-rose-700 bg-rose-50 p-2 rounded-lg" x-text="orderError"></p>
                    </div>
                </template>

                <!-- مساحة الرسائل -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-100/50 relative">
                    <template x-for="chatMessage in activeThread" :key="chatMessage.id">
                        <div
                            class="flex flex-col"
                            :class="chatMessage.direction === 'outbound' ? 'items-end' : 'items-start'"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        >
                            <div
                                class="px-5 py-3.5 rounded-2xl max-w-[80%] shadow-sm"
                                :class="chatMessage.direction === 'outbound' ? 'bg-teal-600 text-white rounded-tr-sm' : 'bg-white text-slate-800 border border-slate-200 rounded-tl-sm'"
                            >
                                <p class="text-sm leading-relaxed whitespace-pre-wrap" x-text="chatMessage.body"></p>

                                <!-- المرفقات -->
                                <template x-if="chatMessage.media && chatMessage.media.length > 0">
                                    <div class="mt-3 grid gap-2">
                                        <template x-for="(m, idx) in chatMessage.media" :key="idx">
                                            <div>
                                                <template x-if="m.type.includes('video')">
                                                    <video :src="m.url" controls class="max-w-full rounded-lg border border-slate-200/20"></video>
                                                </template>
                                                <template x-if="!m.type.includes('video')">
                                                    <img :src="m.url" alt="مرفق" class="max-w-full rounded-lg border border-slate-200/20" />
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <!-- مرجع النزول لأسفل -->
                    <div x-ref="messagesEnd"></div>
                </div>

                <!-- منطقة الإدخال -->
                <div class="bg-white p-4 border-t border-slate-200">
                    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl p-1.5 focus-within:ring-2 focus-within:ring-teal-500/20 focus-within:border-teal-500 transition-all shadow-sm">
                        <input
                            type="text"
                            class="flex-1 bg-transparent p-3 text-sm focus:outline-none text-slate-800 placeholder-slate-400"
                            placeholder="اكتب رسالة للعميل..."
                            x-model="replyText"
                            @keydown.enter="handleSendReply()"
                        />
                        <button
                            @click="handleSendReply()"
                            :disabled="!replyText.trim() || isSendingReply"
                            class="bg-teal-500 text-white w-12 h-12 rounded-xl flex items-center justify-center shadow-md hover:bg-teal-600 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-5 h-5 transform -rotate-180 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </div>
                    <p x-show="messageError" class="mt-2 text-sm text-rose-700 bg-rose-50 p-2 rounded-lg" x-text="messageError"></p>
                </div>
            </div>
        </template>

        <!-- حالة عدم اختيار أي عميل -->
        <template x-if="!selectedCustomer">
            <div
                class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50 w-full"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <div class="w-24 h-24 bg-white shadow-sm rounded-full flex items-center justify-center mb-6 border border-slate-100">
                    <svg class="w-10 h-10 text-teal-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </div>
                <p class="text-xl font-bold text-slate-600">مركز الرسائل موحد</p>
                <p class="text-sm mt-2 text-slate-400 max-w-xs text-center leading-relaxed">اختر محادثة من القائمة الجانبية لمعالجة الطلبات الواردة من منصات التواصل.</p>
            </div>
        </template>
    </div>
</div>

<script>
    function unifiedInbox() {
        return {
            merchantId: localStorage.getItem('merchant_id'),
            token: localStorage.getItem('merchant_token'),
            messages: [],
            merchantProducts: [],
            selectedCustomer: null,
            selectedOrder: null,
            selectedProductId: '',
            selectedQuantity: 1,
            replyText: '',
            orderError: '',
            messageError: '',
            isOrderLoading: false,
            isSendingReply: false,

            get chatList() {
                const uniqueChats = [];
                const customerIds = new Set();

                this.messages.forEach(msg => {
                    if (msg.customer && !customerIds.has(msg.customer.id)) {
                        customerIds.add(msg.customer.id);
                        uniqueChats.push(msg);
                    }
                });

                return uniqueChats;
            },

            get activeThread() {
                if (!this.selectedCustomer) return [];

                return this.messages
                    .filter(msg => msg.customer?.id === this.selectedCustomer.id)
                    .reverse();
            },

            get availableTransitions() {
                const transitions = {
                    new: ['confirmed', 'cancelled'],
                    confirmed: ['prepared', 'cancelled'],
                    prepared: ['shipped', 'cancelled'],
                    shipped: [],
                    cancelled: [],
                };

                return transitions[this.selectedOrder?.status] || [];
            },

            init() {
                Promise.all([this.fetchMessages(), this.fetchProducts()]);
                this.setupEcho();

                this.$watch('messages', () => this.scrollToBottom());
            },

            authHeaders(json = false) {
                const headers = { 'Accept': 'application/json' };

                if (json) {
                    headers['Content-Type'] = 'application/json';
                }

                if (this.token) {
                    headers['Authorization'] = `Bearer ${this.token}`;
                }

                return headers;
            },

            async apiRequest(url, options = {}) {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers: {
                        ...this.authHeaders(Boolean(options.json)),
                        ...(options.headers || {}),
                    },
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (_) {
                    // يبقى payload فارغاً لرسائل أخطاء الوكيل أو الخادم غير JSON.
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'تعذر إتمام العملية.');
                }

                return payload;
            },

            async fetchMessages() {
                try {
                    const data = await this.apiRequest('{{ url("/api/messages") }}');
                    this.messages = data.data || [];
                } catch (error) {
                    console.error('Error fetching messages:', error);
                    this.messageError = error.message;
                }
            },

            async fetchProducts() {
                try {
                    const data = await this.apiRequest('{{ url("/api/products") }}');
                    this.merchantProducts = data.data || [];
                } catch (error) {
                    console.error('Error fetching products:', error);
                    this.orderError = 'تعذر تحميل منتجات التاجر لإضافتها إلى الطلب.';
                }
            },

            async selectCustomer(customer) {
                this.selectedCustomer = customer;
                this.selectedOrder = null;
                this.orderError = '';
                this.messageError = '';
                this.scrollToBottom();

                if (customer.active_order_id) {
                    await this.loadOrder(customer.active_order_id);
                }
            },

            async loadOrder(orderId) {
                this.isOrderLoading = true;
                this.orderError = '';

                try {
                    const data = await this.apiRequest(`{{ url("/api/orders") }}/${orderId}`);
                    this.selectedOrder = data.order;
                } catch (error) {
                    this.orderError = error.message;
                } finally {
                    this.isOrderLoading = false;
                }
            },

            syncCustomerOrderId(customerId, orderId) {
                this.messages.forEach(message => {
                    if (message.customer?.id === customerId) {
                        message.customer.active_order_id = orderId;
                    }
                });

                if (this.selectedCustomer?.id === customerId) {
                    this.selectedCustomer.active_order_id = orderId;
                }
            },

            async handleCreateOrder(customer) {
                if (!customer || customer.active_order_id) return;

                this.isOrderLoading = true;
                this.orderError = '';

                try {
                    const data = await this.apiRequest('{{ url("/api/orders/from-chat") }}', {
                        method: 'POST',
                        json: true,
                        body: JSON.stringify({
                            platform_sender_id: customer.platform_sender_id,
                            platform: customer.platform,
                            name: customer.name,
                            phone_number: customer.phone_number,
                        }),
                    });

                    this.selectedOrder = data.order;
                    this.syncCustomerOrderId(customer.id, data.order.id);
                } catch (error) {
                    this.orderError = error.message;
                } finally {
                    this.isOrderLoading = false;
                }
            },

            async addOrderItem() {
                if (!this.selectedOrder || !this.selectedProductId || this.selectedQuantity < 1) return;

                this.isOrderLoading = true;
                this.orderError = '';

                try {
                    const data = await this.apiRequest(`{{ url("/api/orders") }}/${this.selectedOrder.id}/items`, {
                        method: 'POST',
                        json: true,
                        body: JSON.stringify({
                            product_id: Number(this.selectedProductId),
                            quantity: Number(this.selectedQuantity),
                        }),
                    });

                    this.selectedOrder = data.order;
                    this.selectedProductId = '';
                    this.selectedQuantity = 1;
                } catch (error) {
                    this.orderError = error.message;
                } finally {
                    this.isOrderLoading = false;
                }
            },

            async removeOrderItem(orderItemId) {
                if (!this.selectedOrder) return;

                this.isOrderLoading = true;
                this.orderError = '';

                try {
                    const data = await this.apiRequest(`{{ url("/api/order-items") }}/${orderItemId}`, {
                        method: 'DELETE',
                    });
                    this.selectedOrder = data.order;
                } catch (error) {
                    this.orderError = error.message;
                } finally {
                    this.isOrderLoading = false;
                }
            },

            async handleUpdateTag(newTag) {
                if (!this.selectedOrder) return;

                this.isOrderLoading = true;
                this.orderError = '';

                try {
                    const data = await this.apiRequest(`{{ url("/api/orders") }}/${this.selectedOrder.id}/tag`, {
                        method: 'PATCH',
                        json: true,
                        body: JSON.stringify({ tag: newTag }),
                    });

                    this.selectedOrder = data.order;

                    if (['shipped', 'cancelled'].includes(data.order.status)) {
                        this.syncCustomerOrderId(this.selectedCustomer.id, null);
                    }
                } catch (error) {
                    this.orderError = error.message;
                } finally {
                    this.isOrderLoading = false;
                }
            },

            orderStatusLabel(status) {
                return {
                    new: 'مسودة',
                    confirmed: 'تأكيد الطلب',
                    prepared: 'تجهيز الطلب',
                    shipped: 'تم الشحن',
                    cancelled: 'إلغاء الطلب',
                }[status] || status;
            },

            setupEcho() {
                if (this.merchantId && typeof window.Echo !== 'undefined') {
                    window.Echo.private(`merchant.${this.merchantId}`)
                        .listen('MessageReceived', (event) => {
                            const exists = this.messages.find(message => message.id === event.id);
                            if (exists) return;

                            const knownCustomer = this.messages.find(message => message.customer?.id === event.customer?.id)?.customer;
                            if (knownCustomer && event.customer) {
                                event.customer = { ...knownCustomer, ...event.customer };
                            }

                            this.messages = [event, ...this.messages];
                        });
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    this.$refs.messagesEnd?.scrollIntoView({ behavior: 'smooth' });
                });
            },

            async handleSendReply() {
                if (!this.replyText.trim() || !this.selectedCustomer || this.isSendingReply) return;

                this.messageError = '';
                this.isSendingReply = true;

                try {
                    const responseData = await this.apiRequest(`{{ url("/api/messages") }}/${this.selectedCustomer.id}/reply`, {
                        method: 'POST',
                        json: true,
                        body: JSON.stringify({ body: this.replyText.trim() }),
                    });

                    if (!responseData.data?.id) {
                        throw new Error('لم يؤكد الخادم حفظ الرسالة المرسلة.');
                    }

                    const message = {
                        ...responseData.data,
                        customer: responseData.data.customer || { ...this.selectedCustomer },
                        media: Array.isArray(responseData.data.media) ? responseData.data.media : [],
                    };

                    if (!this.messages.some(item => item.id === message.id)) {
                        this.messages = [message, ...this.messages];
                    }

                    this.replyText = '';
                } catch (error) {
                    console.error('Error sending reply:', error);
                    this.messageError = error.message;
                } finally {
                    this.isSendingReply = false;
                }
            }
        }
    }
</script>
@endsection
