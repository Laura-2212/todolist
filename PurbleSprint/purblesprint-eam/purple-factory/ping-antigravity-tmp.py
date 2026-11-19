import asyncio
from dotenv import load_dotenv
from google.antigravity import Agent, LocalAgentConfig, CapabilitiesConfig
load_dotenv('.env')
async def main():
    config=LocalAgentConfig(system_instructions='Responda somente PRONTO.', capabilities=CapabilitiesConfig(enabled_tools=[]))
    async with Agent(config) as agent:
        response=await agent.chat('Responda somente PRONTO.')
        text=await response.text()
        print('Antigravity autenticação e resposta:', 'OK' if 'PRONTO' in text.upper() else 'resposta recebida')
asyncio.run(main())
