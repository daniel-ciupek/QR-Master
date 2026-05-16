<script setup lang="ts">
import { Bot, Loader2, MessageCircle, Minimize2, Send, X } from 'lucide-vue-next'
import { nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'

interface Message {
    role: 'user' | 'assistant'
    content: string
    streaming?: boolean
}

const { t } = useI18n()

const open = ref(false)
const minimized = ref(false)
const input = ref('')
const messages = ref<Message[]>([])
const streaming = ref(false)
const messagesEl = ref<HTMLElement | null>(null)

const props = defineProps<{ pageContext?: string }>()

function toggle(): void {
    open.value = !open.value
    minimized.value = false
}

function minimize(): void {
    minimized.value = !minimized.value
}

function close(): void {
    open.value = false
}

async function scrollToBottom(): Promise<void> {
    await nextTick()
    if (messagesEl.value) {
        messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    }
}

async function send(): Promise<void> {
    const text = input.value.trim()
    if (!text || streaming.value) return

    input.value = ''
    messages.value.push({ role: 'user', content: text })
    await scrollToBottom()

    const assistantMsg: Message = { role: 'assistant', content: '', streaming: true }
    messages.value.push(assistantMsg)
    streaming.value = true

    try {
        const history = messages.value
            .slice(0, -2)
            .slice(-10)
            .map(m => ({ role: m.role, content: m.content }))

        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''

        const response = await fetch(route('chat.stream'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                message: text,
                history,
                context: props.pageContext ?? '',
            }),
        })

        if (!response.body) throw new Error('No response body')

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let buffer = ''

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            buffer += decoder.decode(value, { stream: true })
            const lines = buffer.split('\n')
            buffer = lines.pop() ?? ''

            for (const line of lines) {
                if (!line.startsWith('data:')) continue
                const raw = line.slice(5).trim()
                if (!raw || raw === '[DONE]') continue

                try {
                    const event = JSON.parse(raw)
                    if (event.type === 'text_delta' && typeof event.text === 'string') {
                        assistantMsg.content += event.text
                        await scrollToBottom()
                    }
                } catch {
                    // skip malformed events
                }
            }
        }
    } catch {
        assistantMsg.content = t('chat.error')
    } finally {
        assistantMsg.streaming = false
        streaming.value = false
        await scrollToBottom()
    }
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        send()
    }
}
</script>

<template>
    <!-- Floating button -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
        <!-- Chat panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="flex w-80 flex-col overflow-hidden rounded-2xl border bg-background shadow-xl"
                :class="minimized ? 'h-14' : 'h-[480px]'"
            >
                <!-- Header -->
                <div class="flex h-14 shrink-0 items-center gap-2 border-b bg-primary/5 px-4">
                    <Bot class="size-5 text-primary" />
                    <span class="flex-1 text-sm font-semibold">{{ t('chat.title') }}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        @click="minimize"
                    >
                        <Minimize2 class="size-3.5" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        @click="close"
                    >
                        <X class="size-3.5" />
                    </Button>
                </div>

                <template v-if="!minimized">
                    <!-- Messages -->
                    <div
                        ref="messagesEl"
                        class="flex-1 overflow-y-auto p-3 space-y-3"
                    >
                        <!-- Welcome -->
                        <div
                            v-if="messages.length === 0"
                            class="flex flex-col items-center gap-2 py-6 text-center text-muted-foreground"
                        >
                            <Bot class="size-8 opacity-40" />
                            <p class="text-sm">{{ t('chat.welcome') }}</p>
                        </div>

                        <!-- Message bubbles -->
                        <div
                            v-for="(msg, i) in messages"
                            :key="i"
                            class="flex"
                            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-[85%] rounded-2xl px-3 py-2 text-sm"
                                :class="msg.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground'"
                            >
                                <span class="whitespace-pre-wrap">{{ msg.content }}</span>
                                <span
                                    v-if="msg.streaming && !msg.content"
                                    class="inline-flex gap-1"
                                >
                                    <span class="animate-bounce">·</span>
                                    <span class="animate-bounce [animation-delay:0.1s]">·</span>
                                    <span class="animate-bounce [animation-delay:0.2s]">·</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="shrink-0 border-t p-3">
                        <div class="flex items-end gap-2">
                            <Textarea
                                v-model="input"
                                :placeholder="t('chat.placeholder')"
                                :disabled="streaming"
                                rows="1"
                                class="min-h-9 max-h-24 resize-none py-2 text-sm"
                                @keydown="onKeydown"
                            />
                            <Button
                                size="icon"
                                :disabled="!input.trim() || streaming"
                                class="size-9 shrink-0"
                                @click="send"
                            >
                                <Loader2
                                    v-if="streaming"
                                    class="size-4 animate-spin"
                                />
                                <Send
                                    v-else
                                    class="size-4"
                                />
                            </Button>
                        </div>
                    </div>
                </template>
            </div>
        </Transition>

        <!-- Toggle button -->
        <Button
            size="icon"
            class="size-12 rounded-full shadow-lg"
            @click="toggle"
        >
            <MessageCircle
                v-if="!open"
                class="size-5"
            />
            <X
                v-else
                class="size-5"
            />
        </Button>
    </div>
</template>
