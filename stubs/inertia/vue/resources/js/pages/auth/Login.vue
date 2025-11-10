<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';
import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import * as loginRoute from '@/routes/login';

const props = defineProps<{
    status?: string;
    canRegister: boolean;
    canResetPassword: boolean;
    canUsePasskeys: boolean;
}>();

interface loginForm {
    email: string;
    password: string;
    passkey: string;
    [key: string]: string | undefined;
}

const loginForm = useForm<loginForm>({
    email: "",
    password: "",
    passkey: ""
})

const showPassword = ref<boolean>(true);

const submit = () => {
    authenticate();
}

const authenticate = async () => {
    if (props.canUsePasskeys && showPassword.value) {
        loginForm.post(loginRoute.store().url, {
            onSuccess: () => loginForm.reset('password')
        })
    } else {
        if (! browserSupportsWebAuthn()) {
            loginForm.setError('passkey', "Your Browser Dosen't support passkeys");
            return;
        }
        const options = await axios.get(passkeysTwoFactor.authenticateOptions().url, {
            params: {
                email: loginForm.email
            }
        })

        try {
            const passkey = await startAuthentication({
                optionsJSON: options.data
            });

            loginForm.passkey = JSON.stringify(passkey);

            await nextTick();

            loginForm.post(passkeysTwoFactor.authenticate().url, {
                onSuccess: () => loginForm.reset('password')
            });
        } catch (error) {
            loginForm.setError('passkey', "Passkey login failed. please try again.");
            console.error(error);
        }
    }
}

watch(showPassword, (value)=>{
    if (value === false)
        authenticate();
    loginForm.clearErrors();
});

</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        v-model="loginForm.email"
                    />
                    <InputError :message="loginForm.errors.email" />
                    <InputError :message="loginForm.errors.passkey" />
                    <InputError :message="loginForm.errors.attempts" />
                </div>

                <div v-if="showPassword" class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        v-model="loginForm.password"
                    />
                    <InputError :message="loginForm.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="loginForm.processing"
                    data-test="login-button"
                >
                    <LoaderCircle
                        v-if="loginForm.processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Log in
                </Button>

                <Button
                    v-if="canUsePasskeys"
                    type="button" 
                    variant="secondary" 
                    class="w-full" 
                    :tabindex="4"
                    :disabled="loginForm.processing"
                    @click="()=>{showPassword = !showPassword}"
                >
                    {{ showPassword ? 'Use Passkey' : 'Use Password' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
                Don't have an account?
                <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
