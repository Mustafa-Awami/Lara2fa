import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/auth-layout';
import emailTwoFactor from '@/routes/email-two-factor';
import twoFactor from '@/routes/two-factor';
import { Head, useForm } from '@inertiajs/react';
import { startAuthentication } from '@simplewebauthn/browser';
import { LoaderCircle } from 'lucide-react';
import { useRef, useState, useEffect } from 'react';
import axios from 'axios';
import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { Separator } from '@/components/ui/separator';
import { Card } from '@/components/ui/card';

interface form {
    code: string;
    recovery_code: string;
    email_code: string;
    [key: string]: string | undefined; //allow for extra keys
}

interface sendCodeForm {
    [key: string]: string | undefined; //allow for extra keys
}

export default function TwoFactorChallenge({ twoFactorMethod, twoFactorEnabled }: {
    twoFactorMethod : "code" | "email_code" | "recovery_code" | "passkeys" | null;
    twoFactorEnabled: {
        authenticatorApp: boolean;
        email: boolean;
        recoveryCodes: boolean;
        passkeys: boolean;
    };
}) {

    const [twoFactorAuthenticationMethod, setTwoFactorAuthenticationMethod] = useState(twoFactorMethod);
    const codeInput = useRef<HTMLInputElement>(null);
    const recoveryCodeInput = useRef<HTMLInputElement>(null);
    const emailInput = useRef<HTMLInputElement>(null);

    const OTP_MAX_LENGTH = 6;

    const form = useForm<form>({
        code: '',
        recovery_code: '',
        email_code: '',
        passkey: ""
    });

    const sendCodeForm = useForm<sendCodeForm>({});

    useEffect(() => {
        if (twoFactorEnabled.recoveryCodes && twoFactorAuthenticationMethod == 'recovery_code') {
            recoveryCodeInput.current?.focus();

        } else if (twoFactorEnabled.authenticatorApp && twoFactorAuthenticationMethod == 'code') {
            codeInput.current?.focus();

        } else if (twoFactorEnabled.email && twoFactorAuthenticationMethod == 'email_code') {
            emailInput.current?.focus();

            if (!sendCodeForm.wasSuccessful) {
                setTimeout(()=>{
                    sendCode();
                }, 1)
            }

        } 
        form.resetAndClearErrors();
    }, [twoFactorAuthenticationMethod]);

    const sendCode = () =>{
        sendCodeForm.post(emailTwoFactor.sendCode().url, {
            errorBag: "EmailTwoFactorAuthenticationNotification",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {

            },
        })
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (twoFactorAuthenticationMethod !== "passkeys")
            form.post(twoFactor.login().url);
        else 
            passkeyAuthenticate();
    };

    const passkeyAuthenticate = async () => {
        const options = await axios.get(passkeysTwoFactor.authenticateOptions().url)

        try {
            const passkey = await startAuthentication({
                optionsJSON: options.data
            });

            form.setData('passkey', JSON.stringify(passkey));
        } catch (error) {
            form.setError('passkey', "Passkey login failed. please try again.");
            console.error(error);
        }
    };

    useEffect(()=>{
        if (twoFactorAuthenticationMethod === "passkeys")
            passkeyAuthenticate();
    },[twoFactorAuthenticationMethod]);

    useEffect(()=>{
        if (form.data.passkey !== "") { 
            form.post(passkeysTwoFactor.authenticate().url)
        };
    }, [form.data.passkey]);

    const getContent = (content: "title" | "description") => {
        if (twoFactorEnabled.recoveryCodes && twoFactorAuthenticationMethod == 'recovery_code') {
            if (content === "title")
                return ('Recovery Code');
            else if (content === "description")
                return ('Please confirm access to your account by entering one of your emergency recovery codes.');

        } else if (twoFactorEnabled.authenticatorApp && twoFactorAuthenticationMethod == 'code') {
            if (content === "title")
                return ('Authenticator App');
            else if (content === "description")
                return ('Please confirm access to your account by entering the authentication code provided by your authenticator application.');

        } else if (twoFactorEnabled.email && twoFactorAuthenticationMethod == 'email_code') {
            if (content === "title")
                return ('Email');
            else if (content === "description")
                return ('Please confirm access to your account by entering the OTP that was sent to your email.');

        } else if (twoFactorEnabled.passkeys && twoFactorAuthenticationMethod == 'passkeys') {
            if (content === "title")
                return ('Passkey');
            else if (content === "description")
                return ('Please confirm access to your account by validating your passkey.');

        } else {
            if (content === "title")
                return ('Two-Factor Authentication');
            else if (content === "description")
                return ('Please choose any of the following two-factor method.');
        }
    };

    return (
        <AuthLayout title={getContent("title")} description={getContent("description")}>
            <Head title="Two-Factor Authentication" />

            {!twoFactorAuthenticationMethod ? (
                <div>
                    <Card className="mt-4 p-4 gap-3">
                        {twoFactorEnabled.authenticatorApp && (<>
                            
                            <div className="group hover:cursor-pointer" onClick={(e)=>{setTwoFactorAuthenticationMethod('code')}}>
                                <h3 className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Authenticator App</h3>
                                <p className="mt-1 text-sm">
                                    Use a mobile authenticator app to get a verification code to log in
                                </p>
                            </div>

                            <Separator className="last:hidden"/>
                        </>)}
                        

                        {twoFactorEnabled.email && (<>
                            
                            <div className="group hover:cursor-pointer" onClick={(e)=>{setTwoFactorAuthenticationMethod('email_code')}}>
                                <h3 className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Email</h3>
                                <p className="mt-1 text-sm">
                                    Use your email to receive an authentication code to log in.
                                </p>
                            </div>

                            <Separator className="last:hidden"/>
                        </>)}

                        {twoFactorEnabled.passkeys && (<>
                            
                            <div className="group hover:cursor-pointer" onClick={(e)=>{setTwoFactorAuthenticationMethod('passkeys')}}>
                                <h3 className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Passkey</h3>
                                <p className="mt-1 text-sm">
                                    Use your registerd passkey to login.
                                </p>
                            </div>

                            <Separator className="last:hidden"/>
                        </>)}
                        
                        {twoFactorEnabled.recoveryCodes && (<>
                            
                            <div className="group hover:cursor-pointer" onClick={(e)=>{setTwoFactorAuthenticationMethod('recovery_code')}}>
                                <h3 className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out group-hover:decoration-current! dark:decoration-neutral-500">Recovery Code</h3>
                                <p className="mt-1 text-sm">
                                    Single-use recovery codes that lets you log in if you don’t have access to your two-factor authentication options.
                                </p>
                            </div>
                            
                        </>)}
                    </Card>
                </div>
            ) : (
                <div>

                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div className="grid gap-4">
                            {(twoFactorAuthenticationMethod === 'code') ? (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            id="code"
                                            name="code"
                                            ref={codeInput}
                                            maxLength={OTP_MAX_LENGTH}
                                            value={form.data.code}
                                            onChange={(value) => form.setData('code', value)}
                                            disabled={form.processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={form.errors.code} />
                                    <InputError message={form.errors.empty} />
                                    <InputError message={form.errors.attempts} />
                                </div>
                            ) : (twoFactorAuthenticationMethod === 'recovery_code') ? (
                                <div className="grid gap-2">
                                    <Input
                                        id="recovery_code"
                                        type="text"
                                        name="recovery_code"
                                        ref={recoveryCodeInput}
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        value={form.data.recovery_code}
                                        placeholder="Enter recovery code"
                                        onChange={(e)=>{form.setData('recovery_code', e.target.value)}}
                                    />
                                    <InputError message={form.errors.recovery_code} />
                                    <InputError message={form.errors.empty} />
                                    <InputError message={form.errors.attempts} />
                                </div>
                            ) : (twoFactorAuthenticationMethod === 'email_code') ? (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">

                                    <span className="text-center text-sm text-muted-foreground">
                                        Didn't receive code? {'   '}
                                        <button onClick={sendCode} className={"cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500 " + (sendCodeForm.processing ? "opacity-25" : "") } disabled={sendCodeForm.processing}>Resend</button>
                                    </span>

                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            id="email_code"
                                            name="email_code"
                                            ref={emailInput}
                                            maxLength={OTP_MAX_LENGTH}
                                            value={form.data.email_code}
                                            onChange={(value) => form.setData('email_code', value)}
                                            disabled={form.processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={form.errors.email_code} />
                                    <InputError message={form.errors.empty} />
                                    <InputError message={form.errors.attempts} />
                                    <InputError message={sendCodeForm.errors.attempts} />
                                </div>
                            ) : (twoFactorAuthenticationMethod === "passkeys") ? (<>
                                <InputError message={form.errors.passkey} />
                                <InputError message={form.errors.attempts} />
                            </>) : null}

                            <Button type="submit" className="w-full" tabIndex={4} disabled={form.processing}>
                                {form.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Log in
                            </Button>
                        </div>
                        
                        <div className="text-center text-sm text-muted-foreground">
                            <button
                                type="button"
                                className="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                onClick={() =>
                                    setTwoFactorAuthenticationMethod(null)
                                }
                            >
                                choose a different method
                            </button>
                        </div>
                    </form>
                </div>
            )}

        </AuthLayout>
    );
}