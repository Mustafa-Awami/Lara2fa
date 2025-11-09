<script setup lang="ts">
import passwordConfirmation from '@/routes/password-confirmation';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

import { RectangleEllipsis } from 'lucide-vue-next';
const props = defineProps({
    isOpen: Boolean
})
const emit = defineEmits(['onPasswordConfimed', 'toggle']);

const passwordInput = ref<HTMLInputElement | null>(null);

const passwordConfirmationForm = useForm({
    password: '',
});

const confirmPassword = () => {
    passwordConfirmationForm.post(passwordConfirmation.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('onPasswordConfimed');
            closePasswordDialog();
        },
        onError: () => passwordInput.value?.focus(),
    });
    
};

const closePasswordDialog = () => {
    emit('toggle', false);

    passwordConfirmationForm.resetAndClearErrors();
};

</script>

<template>
    <Dialog :open="isOpen" @update:open="!$event && closePasswordDialog()">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader class="flex items-center justify-center">
                <div
                    class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                    >
                        <div
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-r border-border last:border-r-0"
                            />
                        </div>
                        <div
                            class="absolute inset-0 grid grid-rows-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`row-${i}`"
                                class="border-b border-border last:border-b-0"
                            />
                        </div>
                        <RectangleEllipsis
                            class="relative z-20 size-6 text-foreground"
                        />
                    </div>
                </div>
                <DialogTitle>
                    Confirm Password
                </DialogTitle>
                <DialogDescription class="text-center">
                    For your security, please confirm your password.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="confirmPassword" class="flex flex-col items-center space-y-5">
                <div class="relative w-full space-y-3">
                    <div class="flex w-full flex-col items-center space-y-3 py-2">
                        <Label htmlFor="password" class="sr-only">Password</Label>

                        <Input
                            id="password"
                            type="password"
                            name="password"
                            ref="passwordInput"
                            v-model="passwordConfirmationForm.password"
                            class="w-full"
                            placeholder="Password"
                        />

                        <InputError :message="passwordConfirmationForm.errors.password" />
                    </div>

                    <div class="flex w-full space-x-5">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="closePasswordDialog"
                            :disabled="passwordConfirmationForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="flex-1"
                            :disabled="passwordConfirmationForm.processing"
                        >
                            Confirm
                        </Button>
                    </div>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>