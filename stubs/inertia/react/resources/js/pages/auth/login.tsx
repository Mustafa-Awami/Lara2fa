import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';
import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import * as loginRoute from '@/routes/login';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canUsePasskeys: boolean;
}

interface loginForm {
    email: string;
    password: string;
    passkey: string;
    [key: string]: string | undefined;
}

export default function Login({ status, canResetPassword, canUsePasskeys }: LoginProps) {

    const loginForm = useForm<loginForm>({
        email: "",
        password: "",
        passkey: ""
    })

    const [showPassword, setShowPassword] = useState<boolean>(true);

    const submit = async (e:React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        authenticate();
    }

    const authenticate = async () => {
        if (canUsePasskeys && showPassword) {
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
                    email: loginForm.data.email
                }
            })
    
            try {
                const passkey = await startAuthentication({
                    optionsJSON: options.data
                });

                loginForm.setData('passkey', JSON.stringify(passkey));
                
            } catch (error) {
                loginForm.setError('passkey', "Passkey login failed. please try again.");
                console.error(error);
            }
        }
    }

    useEffect(()=>{
        if (showPassword === false)
            authenticate();
        loginForm.clearErrors();
    },[showPassword])

    useEffect(()=>{
        if (loginForm.data.passkey !== "") { 
            loginForm.post(passkeysTwoFactor.authenticate().url, {
                onSuccess: () => loginForm.reset('password')
            })
        };
    }, [loginForm.data.passkey]);

    
    return (
        <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            placeholder="email@example.com"
                            onChange={(e) => loginForm.setData('email', e.target.value)}
                        />
                        <InputError message={loginForm.errors.email} />
                        <InputError message={loginForm.errors.passkey} />
                        <InputError message={loginForm.errors.attempts} />
                    </div>

                    {showPassword && (
                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Password</Label>
                                {canResetPassword && (
                                    <TextLink href={request()} className="ml-auto text-sm" tabIndex={5}>
                                        Forgot password?
                                    </TextLink>
                                )}
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Password"
                                onChange={(e) => loginForm.setData('password', e.target.value)}
                            />
                            <InputError message={loginForm.errors.password} />
                        </div>
                    )}

                    <div className="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" tabIndex={3} />
                        <Label htmlFor="remember">Remember me</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={loginForm.processing}>
                        {loginForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Log in
                    </Button>

                    {canUsePasskeys && (
                        <Button onClick={()=>setShowPassword(!showPassword)} type="button" variant={"secondary"} className="w-full" tabIndex={4} disabled={loginForm.processing}>
                            {showPassword ? (<>
                                Use Passkey
                            </>) : (<>
                                Use Password
                            </>)}
                        </Button>
                    )}
                    
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Don't have an account?{' '}
                    <TextLink href={register()} tabIndex={5}>
                        Sign up
                    </TextLink>
                </div>
            </form>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </AuthLayout>
    );
}
