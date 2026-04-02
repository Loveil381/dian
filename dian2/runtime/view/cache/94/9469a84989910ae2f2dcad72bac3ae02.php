<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* 302.html */
class __TwigTemplate_52d20a0467159705102dc39f04233918 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<!doctype html>
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width,initial-scale=1.0\">
    <title>";
        // line 6
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["message"] ?? null), "html", null, true);
        yield "</title>
    <link rel=\"icon\" href=\"/favicon.ico\">
    <link rel=\"stylesheet\" id=\"css-main\" href=\"/assets/admin/css/codebase.min.css\">
</head>
<body>

<div id=\"page-container\" class=\"main-content-boxed\">
    <main id=\"main-container\">
        <div class=\"hero bg-body-extra-light\">
            <div class=\"hero-inner\">
                <div class=\"content content-full\">
                    <div class=\"py-4 text-center\">
                        <div class=\"display-4 fw-bold text-info\">
                            <i class=\"fa fa-lock opacity-50 me-1\"></i> ";
        // line 19
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["message"] ?? null), "html", null, true);
        yield "
                        </div>
                        <h2 class=\"fs-4 fw-medium text-muted mb-5 mt-5\">";
        // line 21
        yield $this->extensions['App\View\Helper']->i18n("我们正在为您的浏览器进行安全重定向，请稍等..");
        yield "</h2>
                        <a class=\"btn btn-lg btn-outline-primary\" href=\"";
        // line 22
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["url"] ?? null), "html", null, true);
        yield "\">
                            立即跳转
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    setTimeout(() => {
        window.location.href = \"";
        // line 33
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["url"] ?? null), "html", null, true);
        yield "\";
    }, 1000 * parseFloat(\"";
        // line 34
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(($context["time"] ?? null), "html", null, true);
        yield "\"))
</script>
</body>
</html>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "302.html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  92 => 34,  88 => 33,  74 => 22,  70 => 21,  65 => 19,  49 => 6,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "302.html", "G:\\kf\\dian2\\app\\View\\302.html");
    }
}
